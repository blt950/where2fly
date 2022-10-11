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
        if(preg_match('/(BKN\d\d\d|OVC\d\d\d)/', $this->metarWithoutRemarks(), $results)){
            foreach($results as $r){
                if((int)substr($r, 3)*100 <= $feet){
                    return true;
                }
            }
        }
        return false;
    }

    public function foggy(){
        // FG | HZ
    }

    public function heavyRain(){
        // +RA +SHRA
    }

    public function heavySnow(){
        // +SN
    }

    public function thunderstorm(){
        // TS
    }

    public function rvrAtBelow(){
        // VV

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