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

}
