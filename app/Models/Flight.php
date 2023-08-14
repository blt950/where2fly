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
        'firsT_seen_at' => 'datetime',
    ];

}
