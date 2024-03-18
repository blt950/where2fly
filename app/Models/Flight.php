<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Flight extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $casts = [
        'last_seen_at' => 'datetime',
        'first_seen_at' => 'datetime',
    ];

    public function departureAirport(){
        return $this->belongsTo(Airport::class, 'airport_dep_id');
    }

    public function arrivalAirport(){
        return $this->belongsTo(Airport::class, 'airport_arr_id');
    }

    public function airline(){
        return $this->belongsTo(Airline::class, 'airline_icao', 'icao_code');
    }

    public function aircrafts(){
        return $this->belongsToMany(Aircraft::class, 'flight_aircraft', 'flight_id', 'aircraft_id');
    }

}
