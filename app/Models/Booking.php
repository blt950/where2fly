<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    /** The bookings API's own id is the primary key — not auto-incrementing locally */
    protected $primaryKey = 'vatsim_booking_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'start' => 'datetime',
            'end' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    public function airport()
    {
        return $this->belongsTo(Airport::class);
    }
}
