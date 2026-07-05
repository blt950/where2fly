<?php

namespace App\Console\Commands;

use App\Helpers\AirportCallsignHelper;
use App\Models\Airport;
use App\Models\AirportScore;
use App\Models\Controller;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchVatsim extends Command
{
    /**
     * How long a live observation stays valid: until the next poll.
     */
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
    protected $signature = 'fetch:vatsim';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch event and controller data from VATSIM';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $processTime = microtime(true);
        $this->info('Fetching and processing VATSIM data');

        $upsertEventsData = [];
        $upsertControllerData = [];

        $airportMap = Airport::select('id', 'icao')->get()->keyBy('icao');

        $this->info('Fetching events...');
        $response = Http::timeout(60)->retry(3, 1000)->get('https://my.vatsim.net/api/v2/events/latest');
        if ($response->successful()) {
            $data = $response->object()->data;

            foreach ($data as $event) {
                if (count($event->airports)) {
                    foreach ($event->airports as $airport) {
                        if (isset($airportMap[$airport->icao])) {
                            $upsertEventsData[] = [
                                'airport_id' => $airportMap[$airport->icao]->id,
                                'event' => $event->name,
                                'start_time' => Carbon::parse($event->start_time),
                                'end_time' => Carbon::parse($event->end_time),
                            ];
                        }
                    }
                }
            }
        }

        $this->info('Fetching online controllers...');
        $response = Http::get('https://data.vatsim.net/v3/vatsim-data.json');

        $vatsimPilots = null;
        if ($response->successful()) {
            $vatsimData = $response->object();
            $vatsimPilots = $vatsimData->pilots;

            foreach ($vatsimData->controllers as $controller) {

                // Skip observers, FSS and enroute controllers
                if ($controller->facility <= 1 || $controller->facility >= 6) {
                    continue;
                }

                // Resolve the position callsign to an airport ICAO
                $callsign = AirportCallsignHelper::resolveIcao($controller->callsign);

                if (! $callsign) {
                    continue;
                }

                if (isset($airportMap[$callsign])) {
                    $upsertControllerData[] = [
                        'airport_id' => $airportMap[$callsign]->id,
                        'callsign' => $controller->callsign,
                        'logon_time' => Carbon::parse($controller->logon_time),
                    ];

                    $this->info('Controller ' . $controller->callsign . ' online at ' . $callsign);
                }
            }
        }

        Event::truncate();
        Event::upsert(
            $upsertEventsData,
            ['airport_id'],
            ['event', 'start_time', 'end_time']
        );

        Controller::truncate();
        Controller::upsert(
            $upsertControllerData,
            ['airport_id'],
            ['callsign', 'logon_time']
        );

        // Free the lookup map before loading full airport models for scoring
        $airportMap = $upsertEventsData = $upsertControllerData = null;

        $this->scoreVatsim($vatsimPilots);

        $this->info('Fetching and scoring of VATSIM data finished in ' . round(microtime(true) - $processTime) . ' seconds');

    }

    /**
     * Rebuild the airport scores this command owns: the live `vatsim` rows,
     * `event` windows and per-controller `logon_estimate` predictions.
     *
     * @param  array|null  $vatsimPilots  the pilots section of vatsim-data.json
     */
    private function scoreVatsim($vatsimPilots): void
    {
        $scoreInsert = [];
        $airports = Airport::where('type', '!=', 'closed')->has('metar')->with('controllers', 'events')->get();

        foreach ($airports as $airport) {

            // Array suffix template for live VATSIM_* scores
            $vatsimArraySuffix = [
                'source' => AirportScore::SOURCE_VATSIM,
                'valid_from' => now(),
                'valid_to' => now()->copy()->addMinutes(self::POLL_INTERVAL_MINUTES),
            ];

            // VATSIM_ATC: Check VATSIM controllers, keeping the earliest logon so the tooltip reflects how long it has been staffed
            if ($airport->controllers->count()) {

                $stations = collect();
                foreach ($airport->controllers as $controller) {
                    $facility = AirportCallsignHelper::facility($controller->callsign);
                    if ($facility === null || $facility == 'OBS') {
                        continue;
                    }

                    if (! isset($stations[$facility]) || $controller->logon_time->lt($stations[$facility])) {
                        $stations[$facility] = $controller->logon_time;
                    }
                }

                $stations = $stations
                    ->map(fn ($logonTime, $facility) => ['facility' => $facility, 'logon_time' => $logonTime])
                    ->sortBy(fn ($station) => ($order = array_search($station['facility'], Airport::ATC_FACILITY_ORDER)) === false ? 99 : $order)
                    ->values();

                $scoreInsert[] = ['airport_id' => $airport->id, 'reason' => 'VATSIM_ATC', 'score' => 1, 'data' => json_encode(['stations' => $stations])] + $vatsimArraySuffix;
            }

            // VATSIM_ATC: Live logins with estimated logoff time (LOGON_ESTIMATE_HOURS)
            foreach ($airport->controllers as $controller) {
                $scoreInsert[] = [
                    'airport_id' => $airport->id,
                    'reason' => 'VATSIM_ATC',
                    'score' => 1,
                    'data' => json_encode([
                        'position' => $controller->callsign,
                        'facility' => AirportCallsignHelper::facility($controller->callsign),
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

                $scoreInsert[] = [
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
                    $scoreInsert[] = ['airport_id' => $airport->id, 'reason' => 'VATSIM_POPULAR', 'score' => 1, 'data' => json_encode(['movements' => $movements])] + $vatsimArraySuffix;
                }
            }
        }

        AirportScore::whereIn('source', [AirportScore::SOURCE_VATSIM, AirportScore::SOURCE_EVENT, AirportScore::SOURCE_LOGON_ESTIMATE])->delete();
        foreach (array_chunk($scoreInsert, 500) as $chunk) {
            AirportScore::insert($chunk);
        }
    }
}
