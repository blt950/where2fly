<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserList extends Model
{
    use HasFactory;

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
