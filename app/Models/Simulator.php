<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Simulator extends Model
{
    use HasFactory;

    public $timestamps = false;

    public function lists()
    {
        return $this->hasMany(UserList::class);
    }
}
