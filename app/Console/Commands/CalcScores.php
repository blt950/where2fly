<?php

namespace App\Console\Commands;

use App\Models\Airport;
use App\Models\AirportScore;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CalcScores extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calc:scores';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate scores of airports';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        // Purge the table for a new calculation
        $processTime = microtime(true);
        $this->info('Starting calculations of aerodrome scores');

        // Fetch VATSIM data
        $vatsimRequest = Http::get('https://data.vatsim.net/v3/vatsim-data.json');
        $vatsimPilots = null;
        if ($vatsimRequest->successful()) {
            $vatsimPilots = json_decode($vatsimRequest->body(), false)->pilots;
        }

        // Grab relevant aerodromes for calculations
        $airports = Airport::where('type', '!=', 'closed')->has('metar')->with('metar', 'runways', 'controllers', 'events')->get();

        $airportScoreInsert = [];
        foreach ($airports as $airport) {

            // Skip airport with old metars
            if (Carbon::now()->gt(Carbon::parse($airport->metar->last_update)->addHour(1))) {
                continue;
            }

            if ($airport->metar->windAtAbove(15)) {
                $airportScoreInsert[] = ['airport_id' => $airport->id, 'reason' => 'METAR_WINDY', 'score' => 1, 'data' => null];
            }

            if ($airport->metar->windGusts()) {
                $airportScoreInsert[] = ['airport_id' => $airport->id, 'reason' => 'METAR_GUSTS', 'score' => 1, 'data' => null];
            }

            $activeRunwayComponents = ['headwind' => 0, 'crosswind' => 0];
            $airportScoreRVRInserted = false;
            foreach ($airport->runways->where('closed', false) as $runway) {
                // Check RVR at runways
                if (
                    $airportScoreRVRInserted == false &&
                    ((! empty($runway->le_ident) && $airport->metar->rvrAtBelow($runway->le_ident, 700)) ||
                    (! empty($runway->he_ident) && $airport->metar->rvrAtBelow($runway->he_ident, 700)))
                ) {
                    $airportScoreInsert[] = ['airport_id' => $airport->id, 'reason' => 'METAR_RVR', 'score' => 1, 'data' => null];
                    $airportScoreRVRInserted = true;
                }

                // Calculate headwind component on active runway
                if (! empty($airport->metar->wind_direction)) {

                    // Fallback to varchar runway identifier if heading is not present in data, which is common.
                    if (empty($runway->le_heading) && ! empty($runway->le_ident)) {
                        $runway->le_heading = rwyIdentToHeading($runway->le_ident);
                    }

                    if (empty($runway->he_heading) && ! empty($runway->he_ident)) {
                        $runway->he_heading = rwyIdentToHeading($runway->he_ident);
                    }

                    // Set the components
                    $headwindComponentLe = abs($airport->metar->wind_speed * cos(($airport->metar->wind_direction - $runway->le_heading) / 180 * 3.14));
                    $crosswindComponentLe = abs($airport->metar->wind_speed * sin(($airport->metar->wind_direction - $runway->le_heading) / 180 * pi()));

                    $headwindComponentHe = abs($airport->metar->wind_speed * cos(($airport->metar->wind_direction - $runway->he_heading) / 180 * 3.14));
                    $crosswindComponentHe = abs($airport->metar->wind_speed * sin(($airport->metar->wind_direction - $runway->he_heading) / 180 * pi()));

                    if ($activeRunwayComponents['headwind'] < $headwindComponentLe) {
                        $activeRunwayComponents['headwind'] = $headwindComponentLe;
                        $activeRunwayComponents['crosswind'] = $crosswindComponentLe;
                    } elseif ($activeRunwayComponents['headwind'] < $headwindComponentHe) {
                        $activeRunwayComponents['headwind'] = $headwindComponentHe;
                        $activeRunwayComponents['crosswind'] = $crosswindComponentHe;
                    }
                }
            }

            // Check if crosswind component is fun at active runway
            if ($airport->metar->wind_speed >= 15 && $activeRunwayComponents['crosswind'] > 12) {
                $airportScoreInsert[] = ['airport_id' => $airport->id, 'reason' => 'METAR_CROSSWIND', 'score' => 1, 'data' => null];
            }

            if ($airport->metar->sightBelow(5000)) {
                $airportScoreInsert[] = ['airport_id' => $airport->id, 'reason' => 'METAR_SIGHT', 'score' => 1, 'data' => null];
            }

            if ($airport->metar->ceilingAtAbove(300)) {
                $airportScoreInsert[] = ['airport_id' => $airport->id, 'reason' => 'METAR_CEILING', 'score' => 1, 'data' => null];
            }

            if ($airport->metar->foggy()) {
                $airportScoreInsert[] = ['airport_id' => $airport->id, 'reason' => 'METAR_FOGGY', 'score' => 1, 'data' => null];
            }

            if ($airport->metar->heavyRain()) {
                $airportScoreInsert[] = ['airport_id' => $airport->id, 'reason' => 'METAR_HEAVY_RAIN', 'score' => 1, 'data' => null];
            }

            if ($airport->metar->heavySnow()) {
                $airportScoreInsert[] = ['airport_id' => $airport->id, 'reason' => 'METAR_HEAVY_SNOW', 'score' => 1, 'data' => null];
            }

            if ($airport->metar->thunderstorm()) {
                $airportScoreInsert[] = ['airport_id' => $airport->id, 'reason' => 'METAR_THUNDERSTORM', 'score' => 1, 'data' => null];
            }

            // Check VATSIM controllers
            if ($airport->controllers->count()) {

                $stations = collect();
                foreach ($airport->controllers->pluck('callsign') as $cs) {
                    $r = substr($cs, -3);
                    if ($r != 'OBS') {
                        $stations[$r] = true;
                    }
                }

                $referenceOrder = ['DEL', 'GND', 'TWR', 'APP', 'CTR'];
                $stations = $stations->keys()->sort(function ($a, $b) use ($referenceOrder) {
                    $aSearch = array_search($a, $referenceOrder);
                    $bSearch = array_search($b, $referenceOrder);

                    return $aSearch - $bSearch;
                });

                $airportScoreInsert[] = ['airport_id' => $airport->id, 'reason' => 'VATSIM_ATC', 'score' => 1, 'data' => $stations->join(', ')];
            }

            // Check if many pilots are departing this airport
            if ($vatsimPilots) {
                $movements = 0;
                foreach ($vatsimPilots as $vp) {
                    if (distance($airport->latitude_deg, $airport->longitude_deg, $vp->latitude, $vp->longitude, 'N') <= 5) {
                        $movements++;
                    }
                }

                if ($movements >= 10) {
                    $airportScoreInsert[] = ['airport_id' => $airport->id, 'reason' => 'VATSIM_POPULAR', 'score' => 1, 'data' => null];

                }
            }

            // Check if ongoing VATSIM event
            foreach ($airport->events as $event) {
                if (Carbon::now()->gt($event->start_time) && Carbon::now()->lt($event->end_time)) {
                    $airportScoreInsert[] = ['airport_id' => $airport->id, 'reason' => 'VATSIM_EVENT', 'score' => 1, 'data' => $event->event . ' until ' . Carbon::parse($event->end_time)->format('H:i\z')];

                }
            }

        }

        AirportScore::truncate();
        AirportScore::insert($airportScoreInsert);

        $this->info('Calculations of ' . $airports->count() . ' aerodromes finished in ' . round(microtime(true) - $processTime) . ' seconds');

    }
}
