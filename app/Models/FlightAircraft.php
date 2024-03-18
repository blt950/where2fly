<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlightAircraft extends Model
{
    use HasFactory;
    public $timestamps = false;

    protected $casts = [
        'last_seen_at' => 'datetime',
        'first_seen_at' => 'datetime',
    ];

    public function flight(){
        return $this->belongsTo(Flight::class, 'flight_id');
    }

    public function aircraft(){
        return $this->belongsTo(Aircraft::class, 'aircraft_id');
    }
}
