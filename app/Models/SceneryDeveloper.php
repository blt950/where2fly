<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SceneryDeveloper extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function airport()
    {
        return $this->belongsTo(Airport::class);
    }

    public function sceneries()
    {
        return $this->hasMany(Scenery::class, 'scenery_developer_id');
    }    
}
