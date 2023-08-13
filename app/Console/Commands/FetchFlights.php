<?php

namespace App\Console\Commands;

use App\Models\Airport;
use App\Models\Flight;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchFlights extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:flights';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch flights from API';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $apiKey = config('app.flights_key');

        $processTime = microtime(true);
        $touchCount = 0;
        $this->info("Starting fetching of flights");

        //$response = Http::get('https://airlabs.co/api/v9/flights?api_key={$apiKey}');
        //$response = Http::get('http://localhost/flights.json');
        $response = Http::get('http://localhost/flights_2.json');
        if($response->successful()){

            $flights = collect(json_decode($response->body(), false)->response);
            
            // Go through the flights and add them to the database if they don't exist
            $upsertData = [];
            $insertData = [];
            foreach($flights as $flight){

                // Skip flights without data we need
                if(
                    !isset($flight->airline_icao) ||
                    !isset($flight->airline_iata) ||
                    !isset($flight->flight_number) ||
                    !isset($flight->flight_icao) ||
                    !isset($flight->dep_icao) ||
                    !isset($flight->arr_icao) ||
                    !isset($flight->aircraft_icao) ||
                    !isset($flight->reg_number)
                ){
                    $this->info('Skipped flight due to missing data');
                    continue;
                }

                // Check if this flight already exists
                $storedFlight = Flight::firstWhere([
                    'flight_icao' => $flight->flight_icao,
                    'dep_icao' => $flight->dep_icao,
                    'arr_icao' => $flight->arr_icao,
                ]);
                
                if($storedFlight != null){

                    // If last update was more than 12 hours ago, update the counter
                    if(now() > $storedFlight->updated_at->addHours(12)){

                        $upsertData[] = [
                            'counter' => $storedFlight->counter + 1,
                            'aircraft_icao' => $flight->aircraft_icao,
                            'reg_number' => $flight->reg_number,
                        ];

                        $this->info('Updated flight ' . $flight->flight_icao . ' (' . $flight->dep_icao . ' - ' . $flight->arr_icao . ')');
                        $touchCount++;
                    } else {
                        $this->info('Skipped flight ' . $flight->flight_icao . ' (' . $flight->dep_icao . ' - ' . $flight->arr_icao . ')');
                    }

                    

                } else {    

                    $departureAirportId = Airport::firstWhere('icao', $flight->dep_icao);
                    $arrivalAirportId = Airport::firstWhere('icao', $flight->arr_icao);

                    // Only create a new model if both airports exist
                    if($departureAirportId && $arrivalAirportId){

                        (isset($flight->reg_number)) ? $regNumber = $flight->reg_number : $regNumber = null;
                        $airportScoreInsert[] = [
                            'airline_icao' => $flight->airline_icao,
                            'airline_iata' => $flight->airline_iata,
                            'flight_number' => $flight->flight_number,
                            'flight_icao' => $flight->flight_icao,
                            'airport_dep_id' => $departureAirportId->id,
                            'dep_icao' => $flight->dep_icao,
                            'airport_arr_id' => $arrivalAirportId->id,
                            'arr_icao' => $flight->arr_icao,
                            'aircraft_icao' => $flight->aircraft_icao,
                            'reg_number' => $regNumber,
                        ];

                        $touchCount++;

                        $this->info('Added flight ' . $flight->flight_icao . ' (' . $flight->dep_icao . ' - ' . $flight->arr_icao . ')');
                    }   
                }
            }

            // Upsert the flights
            Flight::upsert(
                $upsertData,
                ['flight_icao', 'dep_icao', 'arr_icao'],
                ['aircraft_icao', 'reg_number', 'counter']
            );
            Flight::insert($insertData);
            
        } else {
            $this->error("Failed to fetch flights");
        }

        $this->info("Touched ".$touchCount." flights finished in ".round(microtime(true) - $processTime)." seconds");
    }
}
