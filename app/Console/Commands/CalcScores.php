<?php

namespace App\Console\Commands;

use App\Helpers\WeatherScoreHelper;
use App\Models\Airport;
use App\Models\AirportScore;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CalcScores extends Command
{
    /**
     * How long a "now" observation stays valid: METAR rows for the hour the
     * staleness cutoff has always assumed, VATSIM rows until the next poll.
     */
    private const METAR_VALIDITY_HOURS = 1;

    private const POLL_INTERVAL_MINUTES = 30;

    /**
     * Per the issue: estimate 2 hours since logon before a controller logs off.
     */
    private const LOGON_ESTIMATE_HOURS = 2;

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

        $runTime = now();

        // Fetch VATSIM data
        $vatsimRequest = Http::get('https://data.vatsim.net/v3/vatsim-data.json');
        $vatsimPilots = null;
        if ($vatsimRequest->successful()) {
            $vatsimPilots = $vatsimRequest->object()->pilots;
        }

        // Grab relevant aerodromes for calculations
        $airports = Airport::where('type', '!=', 'closed')->has('metar')->with('metar', 'runways', 'controllers', 'events', 'bookings')->get();

        $airportScoreInsert = [];
        foreach ($airports as $airport) {

            // Weather scores from the current METAR — skipped when the METAR is stale,
            // but the VATSIM/prediction scores below are still generated in that case
            if ($runTime->lte(Carbon::parse($airport->metar->last_update)->addHours(self::METAR_VALIDITY_HOURS))) {
                $airportScoreInsert = array_merge($airportScoreInsert, $this->metarScores($airport));
            }

            $vatsimWindow = [
                'source' => AirportScore::SOURCE_VATSIM,
                'valid_from' => $runTime,
                'valid_to' => $runTime->copy()->addMinutes(self::POLL_INTERVAL_MINUTES),
            ];

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

                $airportScoreInsert[] = ['airport_id' => $airport->id, 'reason' => 'VATSIM_ATC', 'score' => 1, 'data' => json_encode(['stations' => $stations->values()])] + $vatsimWindow;
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
                    $airportScoreInsert[] = ['airport_id' => $airport->id, 'reason' => 'VATSIM_POPULAR', 'score' => 1, 'data' => null] + $vatsimWindow;
                }
            }

            // VATSIM events carry their own window, and predict ATC coverage for it
            foreach ($airport->events as $event) {
                if ($runTime->gt($event->end_time)) {
                    continue;
                }

                // The event's start/end already live in valid_from/valid_to — only the name goes in data
                $eventRow = [
                    'airport_id' => $airport->id,
                    'score' => 1,
                    'data' => json_encode(['event' => $event->event]),
                    'source' => AirportScore::SOURCE_EVENT,
                    'valid_from' => $event->start_time,
                    'valid_to' => $event->end_time,
                ];

                $airportScoreInsert[] = ['reason' => 'VATSIM_EVENT'] + $eventRow;
                $airportScoreInsert[] = ['reason' => 'VATSIM_ATC'] + $eventRow;
            }

            // Booked ATC positions predict coverage for their exact window — the
            // facility type (DEL/GND/TWR/APP) drives the tooltip and icon dots
            foreach ($airport->bookings as $booking) {
                $airportScoreInsert[] = [
                    'airport_id' => $airport->id,
                    'reason' => 'VATSIM_ATC',
                    'score' => 1,
                    'data' => json_encode(['callsign' => $booking->callsign, 'facility' => substr(strrchr($booking->callsign, '_'), 1)]),
                    'source' => AirportScore::SOURCE_BOOKING,
                    'valid_from' => $booking->start,
                    'valid_to' => $booking->end,
                ];
            }

            // A controller online right now will probably stay for ~2 hours after logon
            foreach ($airport->controllers as $controller) {
                $estimatedLogoff = $controller->logon_time->copy()->addHours(self::LOGON_ESTIMATE_HOURS);

                $airportScoreInsert[] = [
                    'airport_id' => $airport->id,
                    'reason' => 'VATSIM_ATC',
                    'score' => 1,
                    'data' => json_encode(['position' => $controller->callsign, 'estimated_logoff' => $estimatedLogoff]),
                    'source' => AirportScore::SOURCE_LOGON_ESTIMATE,
                    'valid_from' => $runTime,
                    'valid_to' => $estimatedLogoff,
                ];
            }

        }

        // Full rebuild of every source this command owns — TAF-sourced rows are
        // maintained incrementally by fetch:tafs and must survive this
        AirportScore::whereNot('source', AirportScore::SOURCE_TAF)->delete();
        foreach (array_chunk($airportScoreInsert, 500) as $chunk) {
            AirportScore::insert($chunk);
        }

        $this->info('Calculations of ' . $airports->count() . ' aerodromes finished in ' . round(microtime(true) - $processTime) . ' seconds');

    }

    /**
     * Weather scores from the airport's current METAR, valid from the observation
     * until the next one is expected.
     *
     * @return array<array>
     */
    private function metarScores(Airport $airport): array
    {
        $rows = [];
        $metarWindow = [
            'source' => AirportScore::SOURCE_METAR,
            'valid_from' => $airport->metar->last_update,
            'valid_to' => $airport->metar->last_update->copy()->addHours(self::METAR_VALIDITY_HOURS),
        ];

        foreach (WeatherScoreHelper::reasons($airport->metar) as $reason) {
            $rows[] = ['airport_id' => $airport->id, 'reason' => $reason, 'score' => 1, 'data' => null] + $metarWindow;
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
                $rows[] = ['airport_id' => $airport->id, 'reason' => 'METAR_RVR', 'score' => 1, 'data' => null] + $metarWindow;
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
                $headwindComponentLe = abs($airport->metar->wind_speed * cos(deg2rad($airport->metar->wind_direction - $runway->le_heading)));
                $crosswindComponentLe = abs($airport->metar->wind_speed * sin(deg2rad($airport->metar->wind_direction - $runway->le_heading)));

                $headwindComponentHe = abs($airport->metar->wind_speed * cos(deg2rad($airport->metar->wind_direction - $runway->he_heading)));
                $crosswindComponentHe = abs($airport->metar->wind_speed * sin(deg2rad($airport->metar->wind_direction - $runway->he_heading)));

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
            $rows[] = ['airport_id' => $airport->id, 'reason' => 'METAR_CROSSWIND', 'score' => 1, 'data' => null] + $metarWindow;
        }

        return $rows;
    }
}
