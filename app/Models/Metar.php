<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Metar extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $guarded = [];
    protected $primaryKey = 'icao';

    public function metarWithoutRemarks(){
        return trim(explode("RMK", $this->metar)[0]);
    }

    public function windAtAbove(int $knots){
        $results = [];
        if(preg_match('/(?!^...)(\d\d)(?=KT)/', $this->metarWithoutRemarks(), $results)){
            $airportWind = (int)$results[1];
            return $airportWind >= $knots;
        }
        return false;
    }

    public function ceilingAtAbove(int $feet){
        $results = [];
        if(preg_match('/(BKN\d\d\d|OVC\d\d\d|VV\d\d\d)/', $this->metarWithoutRemarks(), $results)){
            foreach($results as $r){
                if((int)substr($r, 3)*100 <= $feet){
                    return true;
                }
            }
        }
        return false;
    }

    public function foggy(){
        $results = [];
        if(preg_match('/(FG|HZ)/', $this->metarWithoutRemarks(), $results)){
            return true;
        }
        return false;
    }

    public function heavyRain(){
        $results = [];
        if(preg_match('/(\+RA|\+SHRA)/', $this->metarWithoutRemarks(), $results)){
            return true;
        }
        return false;
    }

    public function heavySnow(){
        $results = [];
        if(preg_match('/(\+SN)/', $this->metarWithoutRemarks(), $results)){
            return true;
        }
        return false;
    }

    public function thunderstorm(){
        $results = [];
        if(preg_match('/(TS)/', $this->metarWithoutRemarks(), $results)){
            return true;
        }
        return false;
    }

    public function rvrAtBelow(int $meters){

        // TODO: Process result
        $results = [];
        if(preg_match('/(R\d\d\w?\/M?\d\d\d\d(M|P|V|U|D)?)/', $this->metarWithoutRemarks(), $results)){
            foreach($results as $r){
                if((int)substr($r, 3)*100 <= $feet){
                    return true;
                }
            }
        }
        return false;        

        /*
        R19R/0600N RVR VALUES
        "R" indicates the group followed by the runway heading (06) 
        and the visual range in meters. The report might include a 
        "U" for increasing or "D" for decreasing values.*/
    }

    public function funClouds(){
        // CB || TCU
    }
}