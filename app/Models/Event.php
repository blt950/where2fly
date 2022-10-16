<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $guarded = [];
    protected $dates = [
        'start_time',
        'end_time'
    ];

    public function airport(){
        return $this->belongsTo(Airport::class);
    }
}
