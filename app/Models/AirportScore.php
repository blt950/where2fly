<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AirportScore extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $guarded = [];

    public function airport(){
        return $this->belongsTo(Airport::class);
    }

    public function isWeatherScore(){
        return str_starts_with($this->reason, 'METAR_');
    }

    public function isVatsimScore(){
        return str_starts_with($this->reason, 'VATSIM_');
    }
}
