<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotableAirportTag extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = null;

    protected $fillable = [
        'airport_id',
        'category',
    ];

    public function airport()
    {
        return $this->belongsTo(Airport::class);
    }
}
