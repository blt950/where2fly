<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One row = one airport's current TAF document. The per-period structured
 * fields live in TafForecast; this row keeps the whole document's raw text
 * for display and the issue time driving fetch:tafs' change detection.
 */
class Taf extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'bulletin_time' => 'datetime',
            'valid_from' => 'datetime',
            'valid_to' => 'datetime',
            'last_update' => 'datetime',
        ];
    }

    public function airport()
    {
        return $this->belongsTo(Airport::class);
    }

    public function forecasts()
    {
        return $this->hasMany(TafForecast::class);
    }
}
