<?php

namespace App\Console\Commands;

use App\Models\Aircraft;
use App\Models\Flight;
use App\Models\FlightAircraft;
use Illuminate\Console\Command;

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
        $aircraftTypeConversions = [
            '0000' => null,
            'AT4' => 'AT43',
            'AT7' => 'AT72',
            'CR9' => 'CRJ9',
            'ER4' => 'E145',
            'PL2' => 'PC12',
            'S20' => 'SB20',
            'SF3' => 'SF34',
            'SFB' => 'SF34',
            '100' => 'F100',
            '320' => 'A320',
            '32Q' => 'A21N',
            '733' => 'B733',
            '737' => 'B737',
            '738' => 'B738',
            '73F' => 'B732',
            '73K' => 'B738',
            '76F' => 'B767',
            '77X' => 'B77L',
            'ZZZZ' => null,
        ];

        foreach ($flights as $flight) {
            // Directly attempt to get the converted aircraft type or fallback to the original ICAO code.
            if (array_key_exists($flight->last_aircraft_icao, $aircraftTypeConversions) && $aircraftTypeConversions[$flight->last_aircraft_icao] == null) {
                continue;
            }

            $aircraftType = $aircraftTypeConversions[$flight->last_aircraft_icao] ?? $flight->last_aircraft_icao;

            // Use firstOrCreate method to either find the existing aircraft or create a new one, thereby reducing the code complexity and potential for duplicated entries.
            $aircraft = Aircraft::firstOrCreate(['icao' => $aircraftType], ['icao' => $aircraftType]);

            // Prepare the data for bulk insertion/upsert after the loop.
            $upsertAircraftData[] = [
                'flight_id' => $flight->id,
                'aircraft_id' => $aircraft->id,
                'last_seen_at' => $flight->last_seen_at,
            ];
        }

        // Split array into chunks of 4000 each and upsert each individually
        foreach (array_chunk($upsertAircraftData, 4000) as $chunk) {
            FlightAircraft::upsert(
                $chunk,
                ['flight_id', 'aircraft_icao'],
                ['last_seen_at'],
            );
        }

        return Command::SUCCESS;
    }
}
