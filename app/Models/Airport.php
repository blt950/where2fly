<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public static function findWithCriteria($continent, $country = null, $departureIcao = null, Array $whitelistedArrivals = null, Array $airportExclusions = null){
        
        $returnQuery = Airport::where('type', '!=', 'closed')
        ->whereIn('type', ['large_airport','medium_airport','seaplane_base','small_airport']);
        
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
            
        // Filter out departure airport, get airports with metar, fetch relevant data and run the query
        if(isset($departureIcao)){
            $returnQuery = $returnQuery->where('icao', '!=', $departureIcao);
        }

        if(isset($whitelistedArrivals)){
            $returnQuery = $returnQuery->whereIn('icao', $whitelistedArrivals);
        }

        // Exclude airports
        if(isset($airportExclusions)){
            foreach($airportExclusions as $key => $exclusion){
                if($exclusion == "routes"){
                    $returnQuery = $returnQuery->where('airports.w2f_scheduled_service', false);
                } elseif($exclusion == "airbases"){
                    $returnQuery = $returnQuery->where('airports.w2f_airforcebase', false);
                }
            }
        }

        $result = $returnQuery->has('metar')
        ->with('runways', 'scores', 'metar')
        ->get();

        return $result;
    }

}
