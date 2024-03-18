<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Aircraft extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $fillable = [
        'icao',
    ];

    public function flightAircrafts()
    {
        return $this->hasMany(FlightAircraft::class);
    }

    public function flights()
    {
        return $this->belongsToMany(Flight::class, 'flight_aircraft', 'aircraft_id', 'flight_id');
    }
}
