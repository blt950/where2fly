<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Controller extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $guarded = [];

    protected $dates = [
        'logon_time',
    ];

    public function airport(){
        return $this->belongsTo(Airport::class);
    }
}
