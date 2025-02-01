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
        return $this->belongsToMany(Simulator::class, 'scenery_simulators')->withPivot('link', 'payware', 'published', 'source', 'suggested_by_user_id')->withTimestamps();
    }

    public static function withPublished($published)
    {
        return Scenery::whereHas('simulators', function ($query) use ($published) {
            $query->where('published', $published);
        })
            ->with('simulators')
            ->get();
    }

    public function suggestedByUser()
    {
        return $this->belongsTo(User::class, 'suggested_by_user_id');
    }
}
