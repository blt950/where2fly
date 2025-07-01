<?php

namespace App\Console\Commands;

use App\Models\Airport;
use App\Models\Controller;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchVatsim extends Command
{
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
        $airportMap = Airport::all()->keyBy('icao');

        $this->info('Fetching events...');
        $response = Http::get('https://my.vatsim.net/api/v2/events/latest');
        if ($response->successful()) {
            $data = json_decode($response->body(), false)->data;

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

        if ($response->successful()) {
            $data = json_decode($response->body(), false)->controllers;

            foreach ($data as $controller) {
                $callsign = substr($controller->callsign, 0, 4);

                if (isset($airportMap[$callsign])) {
                    $upsertControllerData[] = [
                        'airport_id' => $airportMap[$callsign]->id,
                        'callsign' => $controller->callsign,
                        'logon_time' => Carbon::parse($controller->logon_time),
                    ];
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

        $this->info('Fetching of VATSIM data finished in ' . round(microtime(true) - $processTime) . ' seconds');

    }
}
