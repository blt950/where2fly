<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Airline extends Model
{
    use HasFactory;

    public $timestamps = false;

    public function flights()
    {
        return $this->hasMany(Flight::class, 'airline_iata', 'iata_code');
    }
}
