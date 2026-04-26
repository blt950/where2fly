<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserList extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'color',
        'simulator_id',
        'user_id',
        'public',
        'hidden',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function simulator()
    {
        return $this->belongsTo(Simulator::class);
    }

    public function airports()
    {
        return $this->belongsToMany(Airport::class);
    }
}
