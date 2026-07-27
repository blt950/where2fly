<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotableAirport extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'airport_id',
        'description',
        'source_url',
    ];

    public function airport()
    {
        return $this->belongsTo(Airport::class);
    }
}
