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
            
            $upsertData = [];
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

                isset($flight->reg_number) ? $regNumber = $flight->reg_number : $regNumber = null;
                $upsertData[] = [
                    'airline_icao' => $flight->airline_icao,
                    'airline_iata' => $flight->airline_iata,
                    'flight_number' => $flight->flight_number,
                    'flight_icao' => $flight->flight_icao,
                    'dep_icao' => $flight->dep_icao,
                    'arr_icao' => $flight->arr_icao,
                    'aircraft_icao' => $flight->aircraft_icao,
                    'reg_number' => $regNumber,
                ];

                $this->info('Touched flight ' . $flight->flight_icao . ' (' . $flight->dep_icao . ' - ' . $flight->arr_icao . ')');
                $touchCount++;

            }

            // Split array into chunks of 4000 each and upsert each individually
            foreach(array_chunk($upsertData, 4000) as $chunk){
                Flight::upsert(
                    $chunk,
                    ['flight_icao', 'dep_icao', 'arr_icao'],
                    ['aircraft_icao', 'reg_number']
                );
            }

            // Loop through all flights without airport_dep_id and link them to Airports model
            Flight::whereNull('airport_dep_id')
                ->join('airports as a', 'flights.dep_icao', '=', 'a.icao')
                ->update(['flights.airport_dep_id' => \DB::raw('a.id')]);

            // Loop through all flights without airport_arr_id and link them to Airports model
            Flight::whereNull('airport_arr_id')
                ->join('airports as a', 'flights.arr_icao', '=', 'a.icao')
                ->update(['flights.airport_arr_id' => \DB::raw('a.id')]);

            // Clean flights with still missing airport_dep_id or airport_arr_id
            Flight::whereNull('airport_dep_id')->orWhereNull('airport_arr_id')->delete();

            // TODO: Update counter
            // Use hex to determine the counting instead? Would be easier than dates. We'd just compare to old hex != new hex.
            
        } else {
            $this->error("Failed to fetch flights. API response not successful.");
        }

        $this->info("Touched ".$touchCount." flights finished in ".round(microtime(true) - $processTime)." seconds");
    }
}
