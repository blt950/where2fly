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

    public function longestRunway(){
        $length = 0;
        foreach($this->runways as $rwy){
            if($rwy->length_ft > $length) $length = $rwy->length_ft;
        }

        return $length;
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

}
