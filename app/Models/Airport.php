<?php

namespace App\Models;

use COM;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use App\Helpers\CalculationHelper;

class Airport extends Model
{
    use HasFactory;
    use HasSpatial;

    public $timestamps = false;
    protected $guarded = [];
    protected $casts = [
        'coordinates' => Point::class,
    ];

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

    /*
    ============================================================
        Search scopes and functions
    ============================================================
    */

    /**
     * Scope a query to only include airports that are considered open and have open runways
     */
    public function scopeAirportOpen(Builder $query){
        $query->where('type', '!=', 'closed')->whereHas('runways', function($query){
            $query->where('closed', false);
        });
    }

    /**
     * Scope a query to only include airports that are not the departure airport
     */
    public function scopeNotIcao(Builder $query, string $icao = null){
        if(isset($icao)){
            $query->where('icao', '!=', $icao);
        }
    }

    /**
     * Scope a query to only include airports that are of the given size
     */
    public function scopeIsAirportSize(Builder $query, Array $destinationAirportSize = null){
        if(isset($destinationAirportSize)){
            $query->whereIn('type', $destinationAirportSize);
        } else {
            $query->whereIn('type', ['small_airport', 'medium_airport', 'large_airport']);
        }
    }

    /**
     * Scope a query to only include airports in the given continent
     */
    public function scopeInContinent(Builder $query, string $continent, string $country = null){

        if(isset($country) && $continent == "DO"){
            $query->where('iso_country', $country);
        } elseif(isset($continent) && $continent != "DO") {
            // Include European and Russian-European airports
            if($continent == "EU"){
                $query->where('airports.continent', $continent)
                ->whereNotIn('airports.iso_region', getRussianAsianRegions());
                            
            // Include Asian and Russian-Asian airports in a nested query for correct logic grouping
            } elseif($continent == "AS"){
                $query->where(function($query) use ($continent){
                    $query->where('airports.continent', $continent)
                    ->orWhereIn('airports.iso_region', getRussianAsianRegions());
                });

            // Filter only on continent
            } else {
                $query->where('airports.continent', $continent);
            }
        }
    }

    /**
     * Scope a query to only include airports within the given distance
     */
    public function scopeWithinDistance(Builder $query, string $codeletter, int $airtimeMin, int $airtimeMax, string $departureIcao){
        [$minDistance, $maxDistance] = CalculationHelper::aircraftNmPerHourRange($codeletter, $airtimeMin, $airtimeMax);
        if(isset($departureIcao)){
            $departureAirport = Airport::select('coordinates')->where('icao', $departureIcao)->orWhere('local_code', $departureIcao)->first();
            $query->whereDistance('coordinates', $departureAirport->coordinates, '<=', $maxDistance)->whereDistance('coordinates', $departureAirport->coordinates, '>=', $minDistance);
        }
    }

    /**
     * Scope a query to only include airports that have runways with lights
     */
    public function scopeFilterRunwayLights(Builder $query, int $destinationRunwayLights = null){
        if(isset($destinationRunwayLights) && $destinationRunwayLights !== 0){
            
            if($destinationRunwayLights == 1){
                $query->whereHas('runways', function($query){
                    $query->where('lighted', true);
                });
            } else if($destinationRunwayLights == -1){
                $query->whereDoesntHave('runways', function($query){
                    $query->where('lighted', true);
                });
            }

        }
    }

    /**
     * Scope a query to only include airports that are airbases
     */
    public function scopeFilterAirbases(Builder $query, int $destinationAirbases = null){
        if(isset($destinationAirbases) && $destinationAirbases !== 0){
            
            if($destinationAirbases == 1){
                $query->where('w2f_airforcebase', true);
            } else if($destinationAirbases == -1){
                $query->where('w2f_airforcebase', false);
            }

        }
    }

    /**
     * Scope a query to only include airports that have scores
     */
    public function scopeFilterByScores(Builder $query, Array $filterByScores = null){
        if(isset($filterByScores) && !empty($filterByScores)){
            
            $query->where(function($query) use ($filterByScores){
                foreach($filterByScores as $score => $value){
                    if($value == 1){
                        $query->whereHas('scores', function($query) use ($score){
                            $query->where('reason', $score);
                        });
                    } else if($value == -1){
                        $query->whereDoesntHave('scores', function($query) use ($score){
                            $query->where('reason', $score);
                        });
                    }
                }
            });

        }
    }

    /**
     * Scope a query to only include airports that have routes and airlines
     */
    public function scopeFilterRoutesAndAirlines(Builder $query, string $departureIcao = null, Array $filterByAirlines = null, Array $filterByAircrafts = null, int $destinationWithRoutesOnly = null, string $flightDirection = 'arrivalFlights'){
        if(isset($destinationWithRoutesOnly) && $destinationWithRoutesOnly !== 0){
            
            if($destinationWithRoutesOnly == 1){
                $query->whereHas($flightDirection, function($query) use ($departureIcao, $filterByAirlines, $flightDirection, $filterByAircrafts){
                    
                    if(isset($departureIcao)){
                        if($flightDirection == 'arrivalFlights'){
                            $query->where('dep_icao', $departureIcao);
                        } else {
                            $query->where('arr_icao', $departureIcao);
                        }
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
                $query->whereDoesntHave($flightDirection, function($query) use ($departureIcao, $filterByAirlines, $flightDirection, $filterByAircrafts){
                    
                    if(isset($departureIcao)){
                        if($flightDirection == 'arrivalFlights'){
                            $query->where('dep_icao', $departureIcao);
                        } else {
                            $query->where('arr_icao', $departureIcao);
                        }
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
            $query->whereHas($flightDirection, function($query) use ($departureIcao, $filterByAirlines, $filterByAircrafts){
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
    }

    /**
     * Scope a query to only include airports that have the given scores
     */
    public function scopeReturnOnlyWhitelistedIcao(Builder $query, Array $whitelistedArrivals = null){
        if(isset($whitelistedArrivals)){
            $query->whereIn('icao', $whitelistedArrivals);
        }
    }

}
