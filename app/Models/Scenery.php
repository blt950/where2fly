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

    public function developer()
    {
        return $this->belongsTo(SceneryDeveloper::class, 'scenery_developer_id');
    }

    public function simulator()
    {
        return $this->belongsTo(Simulator::class);
    }

    public function suggestedByUser()
    {
        return $this->belongsTo(User::class, 'suggested_by_user_id');
    }
}
