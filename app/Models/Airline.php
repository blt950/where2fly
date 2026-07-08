<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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

    /**
     * The stored code may carry a * (marks duplicate IATA codes) — strip it
     * for display and logo filenames
     */
    protected function iataCode(): Attribute
    {
        return Attribute::get(fn (?string $value) => $value === null ? null : str_replace('*', '', $value));
    }
}
