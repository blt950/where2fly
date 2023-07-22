<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Metar extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $dates = [
        'last_updated',
    ];

    protected $guarded = [];

    public function airport(){
        return $this->belongsTo(Airport::class);
    }

    public function metarWithoutRemarks(){
        return trim(explode("RMK", $this->metar)[0]);
    }

    public function isVisualCondition(){
        return $this->sightAtAbove(5000) && !$this->ceilingAtAbove(999);        
    }

    public function sightAtAbove(int $meters){
        // Check sight
        $results = [];
        if(preg_match('/\s(\d\d\d\d)\s/', $this->metarWithoutRemarks(), $results)){
            if((int)$results[1] >= $meters) return true;
        }

        // Check american sight. 10SM == 9999
        $results = [];
        if(preg_match('/\s(\d\d?)SM\s/', $this->metarWithoutRemarks(), $results)){
            if((int)$results[1]*1609.344 >= $meters) return true;
        }

        // There's no ceiling
        if(preg_match('/(CAVOK|NSC)/', $this->metarWithoutRemarks(), $results)){
            return true;
        }

        return false;
    }

    public function sightBelow(int $meters){
        // Check sight
        $results = [];
        if(preg_match('/\s(\d\d\d\d)\s/', $this->metarWithoutRemarks(), $results)){
            if((int)$results[1] < $meters) return true;
        }

        // Check american sight. 10SM == 9999
        $results = [];
        if(preg_match('/\s(\d\d?)SM\s/', $this->metarWithoutRemarks(), $results)){
            if((int)$results[1]*1609.344 < $meters) return true;
        }

        return false;
    }

    public function windAtAbove(int $knots){
        if($this->wind_speed){
            return $this->wind_speed >= $knots;
        }
        return false;
    }

    public function windGusts(){
        if($this->wind_gusts){
            return true;
        }
    }

    public function ceilingAtAbove(int $feet){
        $results = [];
        if(preg_match_all('/(BKN\d\d\d|OVC\d\d\d|VV\d\d\d)/', $this->metarWithoutRemarks(), $results, PREG_SET_ORDER)){
            $results = array_shift($results); // Remove first occurence which is the whole matched string
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
        if(preg_match('/(TS|\+TSRA)/', $this->metarWithoutRemarks(), $results)){
            return true;
        }
        return false;
    }

    public function rvrAtBelow(string $rwy, int $meters){
        $results = [];
        if(preg_match_all('/R(\d\d\w?)\/M?(\d\d\d\d)(M|P|V|U|D)?\s/', $this->metarWithoutRemarks(), $results, PREG_SET_ORDER)){
            foreach($results as $r){
                if($r[1] == $rwy){
                    if((int)$r[2] <= $meters){
                        return true;
                    }
                }
            }
        }
        return false;
    }
}