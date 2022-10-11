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

    public function windAtAbove(int $knots){
        $results = [];
        if(preg_match('/(?!^...)(\d\d)(?=KT)/', $this->metar, $results)){
            $airportWind = (int)$results[1];
            return $airportWind >= $knots;
        }
        return false;
    }

    public function ceilingAtAbove(int $feet){
        $results = [];
        $metar = explode("RMK", $this->metar)[0];  // Remove the RMK parts
        if(preg_match('/(BKN\d\d\d|OVC\d\d\d)/', $metar, $results)){
            foreach($results as $r){
                if((int)substr($r, 3)*100 <= $feet){
                    return true;
                }
            }
        }
        return false;
    }

    public function foggy(){
        // FG
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
    }

    public function funClouds(){
        // CB || TCU
    }
}