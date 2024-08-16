<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Runway extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'lighted' => 'boolean',
        'closed' => 'boolean',
    ];

    public function airport()
    {
        return $this->belongsTo(Airport::class);
    }
}
