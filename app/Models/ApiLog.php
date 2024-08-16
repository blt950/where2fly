<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    public $fillable = ['api_key_id', 'time'];

    public function key()
    {
        return $this->belongsTo(ApiKey::class);
    }
}
