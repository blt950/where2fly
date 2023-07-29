<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiKey extends Model
{
    use HasFactory;

    public $table = 'api_keys';

    public $timestamps = false;

    public $fillable = [
        'key', 'name', 'ip_address', 'last_used_at',
    ];

    public $casts = [
        'disabled' => 'boolean',
    ];

    public function logs(){
        return $this->hasMany(ApiLog::class);
    }
}
