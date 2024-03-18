<?php

namespace App\Models;

use COM;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Airport extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $guarded = [];

    public function metar(){
        return $this->hasOne(Metar::class);
    }

    public function runways(){
        return $this->hasMany(Runway::class);
    }

    public function scores(){
        return $this->hasMany(AirportScore::class);
    }

    public function events(){
        return $this->hasMany(Event::class);
    }

    public function controllers(){
        return $this->hasMany(Controller::class);
    }

    public function arrivalFlights(){
        return $this->hasMany(Flight::class, 'airport_arr_id');
    }

    public function departureFlights(){
        return $this->hasMany(Flight::class, 'airport_dep_id');
    }

    public function departureFlightsTo($toIcao, $seenThreshold = 3){
        return $this->departureFlights()->where('arr_icao', $toIcao)->where('seen_counter', '>', $seenThreshold)->get()->groupBy('airline_icao');
    }

    public function hasWeatherScore(){
        foreach($this->scores as $s){
            if($s->isWeatherScore()) return true;
        }
        return false;
    }

    public function weatherScore(){
        $score = 0;
        foreach($this->scores as $s){
            if($s->isWeatherScore()){
                $score++;
            }
        }
        return $score;
    }

    public function hasVatsimScore(){
        foreach($this->scores as $s){
            if($s->isVatsimScore()) return true;
        }
        return false;
    }

    public function vatsimScore(){
        $score = 0;
        foreach($this->scores as $s){
            if($s->isVatsimScore()){
                $score++;
            }
        }
        return $score;
    }

    public function longestRunway(){
        $length = 0;
        foreach($this->runways as $rwy){
            if($rwy->closed == false && $rwy->length_ft > $length) $length = $rwy->length_ft;
        }

        return $length;
    }

    public function hasVisualCondition(){
        return $this->metar->isVisualCondition();
    }

    public function supportsAircraftCode(string $code){

        $reqRwyLength = 0;
        switch($code){
            case "A":
                $reqRwyLength = 800;
                break;
            case "B":
                $reqRwyLength = 2500;
                break;
            case "C":
                $reqRwyLength = 6000;
                break;
            case "D":
                $reqRwyLength = 6500;
                break;
            case "E":
                $reqRwyLength = 7500;
                break;
            case "F":
                $reqRwyLength = 8000;
                break;
            default:
                $reqRwyLength = 0;
        }

        if($this->runways->where('closed', false)->where('length_ft', '>=', $reqRwyLength)->count()){
            return true;
        }

        return false;

    }

    public static function findWithCriteria(
            string $continent = null, 
            string $country = null,    
            string $departureIcao = null, 
            Array $destinationAirportSize = null,
            Array $whitelistedArrivals = null,
            Array $filterByScores = null, 
            int $destinationRunwayLights = null, 
            int $destinationAirbases = null, 
            int $destinationWithRoutesOnly = null, 
            Array $filterByAirlines = null,
            Array $filterByAircrafts = null,
            string $flightDirection = 'arrivalFlights'
        ){

        $returnQuery = Airport::where('type', '!=', 'closed');
        
        // If the filter is domestic
        if(isset($country) && $continent == "DO"){
            $returnQuery = $returnQuery->where('iso_country', $country);
        } elseif(isset($continent) && $continent != "DO") {

            // Include European and Russian-European airports
            if($continent == "EU"){
                $returnQuery = $returnQuery->where('airports.continent', $continent)
                ->whereNotIn('airports.iso_region', getRussianAsianRegions());
                            
            // Include Asian and Russian-Asian airports in a nested query for correct logic grouping
            } elseif($continent == "AS"){
                $returnQuery = $returnQuery->where(function($query) use ($continent){
                    $query->where('airports.continent', $continent)
                    ->orWhereIn('airports.iso_region', getRussianAsianRegions());
                });

            // Filter only on continent
            } else {
                $returnQuery = $returnQuery->where('airports.continent', $continent);
            }
        }

        // Only airports with open runways
        $returnQuery = $returnQuery->whereHas('runways', function($query){
            $query->where('closed', false);
        });

        // Destination airport size
        if(isset($destinationAirportSize)){
            $returnQuery = $returnQuery->whereIn('type', $destinationAirportSize);
        } else {
            $returnQuery = $returnQuery->whereIn('type', ['small_airport', 'medium_airport', 'large_airport']);
        }
            
        // Filter out departure airport, get airports with metar, fetch relevant data and run the query
        if(isset($departureIcao)){
            $returnQuery = $returnQuery->where('icao', '!=', $departureIcao);
        }

        if(isset($whitelistedArrivals)){
            $returnQuery = $returnQuery->whereIn('icao', $whitelistedArrivals);
        }

        if(isset($filterByScores) && !empty($filterByScores)){
            
            $returnQuery = $returnQuery->where(function($returnQuery) use ($filterByScores){
                foreach($filterByScores as $score => $value){
                    if($value == 1){
                        $returnQuery = $returnQuery->whereHas('scores', function($query) use ($score){
                            $query->where('reason', $score);
                        });
                    } else if($value == -1){
                        $returnQuery = $returnQuery->whereDoesntHave('scores', function($query) use ($score){
                            $query->where('reason', $score);
                        });
                    }
                }
            });

        }

        // Only airports with runway lights
        if(isset($destinationRunwayLights) && $destinationRunwayLights !== 0){
            
            if($destinationRunwayLights == 1){
                $returnQuery = $returnQuery->whereHas('runways', function($query){
                    $query->where('lighted', true);
                });
            } else if($destinationRunwayLights == -1){
                $returnQuery = $returnQuery->whereDoesntHave('runways', function($query){
                    $query->where('lighted', true);
                });
            }

        }

        // Destinations that are airbases
        if(isset($destinationAirbases) && $destinationAirbases !== 0){
            
            if($destinationAirbases == 1){
                $returnQuery = $returnQuery->where('w2f_airforcebase', true);
            } else if($destinationAirbases == -1){
                $returnQuery = $returnQuery->where('w2f_airforcebase', false);
            }

        }

        // Only airports with routes to the arrival airport
        if(isset($destinationWithRoutesOnly) && $destinationWithRoutesOnly !== 0){
            
            if($destinationWithRoutesOnly == 1){
                $returnQuery = $returnQuery->whereHas($flightDirection, function($query) use ($departureIcao, $filterByAirlines, $flightDirection, $filterByAircrafts){
                    if($flightDirection == 'arrivalFlights'){
                        $query->where('dep_icao', $departureIcao);
                    }   

                    $query->where('flights.seen_counter', '>', 3);

                    if(isset($filterByAirlines)){
                        $query->whereIn('airline_icao', $filterByAirlines);
                    }

                    if(isset($filterByAircrafts)){
                        $query->whereHas('aircrafts', function($query) use ($filterByAircrafts){
                            $query->whereIn('aircraft.icao', $filterByAircrafts);
                        });
                    }
                });
            } else if($destinationWithRoutesOnly == -1){
                $returnQuery = $returnQuery->whereDoesntHave($flightDirection, function($query) use ($departureIcao, $filterByAirlines, $flightDirection, $filterByAircrafts){
                    if($flightDirection == 'arrivalFlights'){
                        $query->where('dep_icao', $departureIcao);
                    }

                    $query->where('flights.seen_counter', '>', 3);

                    if(isset($filterByAirlines)){
                        $query->whereIn('airline_icao', $filterByAirlines);
                    }

                    if(isset($filterByAircrafts)){
                        $query->whereHas('aircrafts', function($query) use ($filterByAircrafts){
                            $query->whereIn('aircraft.icao', $filterByAircrafts);
                        });
                    }
                });
            }

        } else if(isset($filterByAirlines) || isset($filterByAircrafts)) {
            $returnQuery = $returnQuery->whereHas($flightDirection, function($query) use ($departureIcao, $filterByAirlines, $filterByAircrafts){
                if(isset($departureIcao)){

                    if($filterByAirlines){
                        $query->where('dep_icao', $departureIcao)->where('flights.seen_counter', '>', 3)->whereIn('airline_icao', $filterByAirlines);
                    }

                    if($filterByAircrafts){
                        $query->where('dep_icao', $departureIcao)->where('flights.seen_counter', '>', 3)->whereHas('aircrafts', function($query) use ($filterByAircrafts){
                            $query->whereIn('aircraft.icao', $filterByAircrafts);
                        });
                    }
                    
                } else {

                    if($filterByAirlines){
                        $query->where('flights.seen_counter', '>', 3)->whereIn('airline_icao', $filterByAirlines);
                    }

                    if($filterByAircrafts){
                        $query->where('flights.seen_counter', '>', 3)->whereHas('aircrafts', function($query) use ($filterByAircrafts){
                            $query->whereIn('aircraft.icao', $filterByAircrafts);
                        });
                    }
                }
            });
        }

        $result = $returnQuery->has('metar')
        ->with('runways', 'scores', 'metar')
        ->get();

        return $result;
    }

}
