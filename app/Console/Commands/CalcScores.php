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

    private const POLL_INTERVAL_MINUTES = 60;

    /**
     * Estimate an online controller without a booking to sit two hours before logging off.
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
            // Generate scores for the current METAR if valid, VATSIM and predictions.
            if (now()->lte(Carbon::parse($airport->metar->last_update)->addHours(self::METAR_VALIDITY_HOURS))) {
                $airportScoreInsert = array_merge($airportScoreInsert, $this->metarScores($airport));
            }

            // Array suffix template for VATSIM_* scores
            $vatsimArraySuffix = [
                'source' => AirportScore::SOURCE_VATSIM,
                'valid_from' => now(),
                'valid_to' => now()->copy()->addMinutes(self::POLL_INTERVAL_MINUTES),
            ];

            // VATSIM_ATC: Check VATSIM controllers, keeping the earliest logon so the tooltip reflects how long it has been staffed
            if ($airport->controllers->count()) {

                $stations = collect();
                foreach ($airport->controllers as $controller) {
                    $facility = substr(strrchr($controller->callsign, '_'), 1);
                    if ($facility == 'OBS') {
                        continue;
                    }

                    if (! isset($stations[$facility]) || $controller->logon_time->lt($stations[$facility])) {
                        $stations[$facility] = $controller->logon_time;
                    }
                }

                $referenceOrder = ['DEL', 'GND', 'TWR', 'APP', 'CTR'];
                $stations = $stations
                    ->map(fn ($logonTime, $facility) => ['facility' => $facility, 'logon_time' => $logonTime])
                    ->sortBy(fn ($station) => ($order = array_search($station['facility'], $referenceOrder)) === false ? 99 : $order)
                    ->values();

                $airportScoreInsert[] = ['airport_id' => $airport->id, 'reason' => 'VATSIM_ATC', 'score' => 1, 'data' => json_encode(['stations' => $stations])] + $vatsimArraySuffix;
            }

            // VATSIM_ATC: Fetch booked positions by callsign
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

            // VATSIM_ATC: Live logins with estimated logoff time (LOGON_ESTIMATE_HOURS)
            foreach ($airport->controllers as $controller) {
                $airportScoreInsert[] = [
                    'airport_id' => $airport->id,
                    'reason' => 'VATSIM_ATC',
                    'score' => 1,
                    'data' => json_encode([
                        'position' => $controller->callsign,
                        'facility' => substr(strrchr($controller->callsign, '_'), 1),
                        'logon_time' => $controller->logon_time,
                    ]),
                    'source' => AirportScore::SOURCE_LOGON_ESTIMATE,
                    'valid_from' => now(),
                    'valid_to' => $controller->logon_time->copy()->addHours(self::LOGON_ESTIMATE_HOURS),
                ];
            }

            // VATSIM_EVENT: Fetch non finished events
            foreach ($airport->events as $event) {
                if (now()->gt($event->end_time)) {
                    continue;
                }

                $airportScoreInsert[] = [
                    'airport_id' => $airport->id,
                    'reason' => 'VATSIM_EVENT',
                    'score' => 1,
                    'data' => json_encode(['event' => $event->event]),
                    'source' => AirportScore::SOURCE_EVENT,
                    'valid_from' => $event->start_time,
                    'valid_to' => $event->end_time,
                ];
            }

            // VATSIM_POPULAR: Check if many pilots are moving around the airport
            if ($vatsimPilots) {
                $movements = 0;
                foreach ($vatsimPilots as $vp) {
                    if (distance($airport->latitude_deg, $airport->longitude_deg, $vp->latitude, $vp->longitude, 'N') <= 5) {
                        $movements++;
                    }
                }

                if ($movements >= 10) {
                    $airportScoreInsert[] = ['airport_id' => $airport->id, 'reason' => 'VATSIM_POPULAR', 'score' => 1, 'data' => json_encode(['movements' => $movements])] + $vatsimArraySuffix;
                }
            }
        }

        // Cleanup scores table except columns which are not TAF's
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
        $scores = [];
        $metarArraySuffix = [
            'source' => AirportScore::SOURCE_METAR,
            'valid_from' => $airport->metar->last_update,
            'valid_to' => $airport->metar->last_update->copy()->addHours(self::METAR_VALIDITY_HOURS),
        ];

        // Fill in scores from current METAR observation
        foreach (WeatherScoreHelper::reasons($airport->metar) as $reason) {
            $scores[] = ['airport_id' => $airport->id, 'reason' => $reason, 'score' => 1, 'data' => null] + $metarArraySuffix;
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
                $scores[] = ['airport_id' => $airport->id, 'reason' => 'METAR_RVR', 'score' => 1, 'data' => null] + $metarArraySuffix;
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
            $scores[] = ['airport_id' => $airport->id, 'reason' => 'METAR_CROSSWIND', 'score' => 1, 'data' => null] + $metarArraySuffix;
        }

        return $scores;
    }
}
