<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Scenery extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function airport()
    {
        return $this->belongsTo(Airport::class);
    }

    public function simulators()
    {
        return $this->belongsToMany(Simulator::class, 'scenery_simulators')->withTimestamps();
    }

    public function suggestedByUser()
    {
        return $this->belongsTo(User::class, 'suggested_by_user_id');
    }
}
