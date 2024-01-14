<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Flight;
use App\Models\FlightAircraft;

class EnrichFlights extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'enrich:flights';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enrich flight data with aircraft types';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Get flights that have been seen within the last 6 hours and upsert the aircraft_icao to the flight_aircrafts table
        $flights = Flight::where('last_seen_at', '>=', now()->subHours(6))->get();
        $upsertAircraftData = [];
        foreach($flights as $flight){
            $upsertAircraftData[] = [
                'flight_id' => $flight->id,
                'aircraft_icao' => $flight->last_aircraft_icao,
                'last_seen_at' => $flight->last_seen_at,
            ];
        }

        // Split array into chunks of 4000 each and upsert each individually
        foreach(array_chunk($upsertAircraftData, 4000) as $chunk){
            FlightAircraft::upsert(
                $chunk,
                ['flight_id', 'aircraft_icao'],
                ['last_seen_at'],
            );
        }

        return Command::SUCCESS;
    }
}
