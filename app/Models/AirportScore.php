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
}
