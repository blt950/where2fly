<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Airport;
use App\Models\Controller;
use App\Models\Event;

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
        $this->info("Fetching and processing VATSIM data");

        Event::truncate();
        Controller::truncate();

        $this->info("Fetching events...");
        $response = Http::get('https://my.vatsim.net/api/v1/events/all');
        if($response->successful()){
            $data = json_decode($response->body(), false)->data;

            foreach($data as $event){
                if(count($event->airports)){
                    foreach($event->airports as $airport){
                        Event::create([
                            'airport_id' => Airport::where('icao', $airport->icao)->get()->first()->id,
                            'event' => $event->name,
                            'start_time' => $event->start_time,
                            'end_time' => $event->end_time
                        ]);
                    }
                }
            }

        }

        $this->info("Fetching online controllers...");
        $response = Http::get('https://data.vatsim.net/v3/vatsim-data.json');
        if($response->successful()){
            $data = json_decode($response->body(), false)->controllers;

            foreach($data as $controller){
                $callsign = substr($controller->callsign, 0, 4);
                if(Airport::where('icao', $callsign)->get()->count()){
                    Controller::create([
                        'airport_id' => Airport::where('icao', $callsign)->get()->first()->id,
                        'callsign' => $controller->callsign,
                        'logon_time' => $controller->logon_time,
                    ]);
                }
            }

        }


        $this->info("Fetching of VATSIM data finished in ".round(microtime(true) - $processTime)." seconds");

    }
}
