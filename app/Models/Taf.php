<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One row = one forecast period of a TAF document. Same method names as Metar
 * so scoring can treat a period like an observation, but the implementations
 * read the pre-parsed fields from the aviationweather.gov cache XML instead of
 * regexing raw text.
 */
class Taf extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'sky_condition' => 'array',
            'issued_at' => 'datetime',
            'valid_from' => 'datetime',
            'valid_to' => 'datetime',
            'last_update' => 'datetime',
        ];
    }

    public function airport()
    {
        return $this->belongsTo(Airport::class);
    }

    /**
     * Whether this period should generate airport scores. Base, FM and BECMG
     * periods are always scored. TEMPO/PROB periods are only scored when they
     * carry a probability percentage to show the user — a bare TEMPO was never
     * asserted as a forecast for its full window and has no percentage to attach.
     */
    public function isScoreable()
    {
        if ($this->probability !== null) {
            return true;
        }

        return $this->change_indicator === null || in_array($this->change_indicator, ['FM', 'BECMG']);
    }

    public function windAtAbove(int $knots)
    {
        if ($this->wind_speed_kt) {
            return $this->wind_speed_kt >= $knots;
        }

        return false;
    }

    public function windGusts()
    {
        if ($this->wind_gust_kt) {
            return true;
        }
    }

    /**
     * Visibility in statute miles, as a float. A trailing `+` (e.g. `6+`)
     * means "at or above" that value and is treated as the value itself.
     */
    protected function visibilityMeters()
    {
        if ($this->visibility_statute_mi === null) {
            return null;
        }

        return (float) rtrim($this->visibility_statute_mi, '+') * 1609.344;
    }

    public function sightAtAbove(int $meters)
    {
        $visibility = $this->visibilityMeters();

        return $visibility !== null && $visibility >= $meters;
    }

    public function sightBelow(int $meters)
    {
        $visibility = $this->visibilityMeters();

        // An unbounded value like `6+` or `10+` can never assert visibility below anything above it
        return $visibility !== null && $visibility < $meters && ! str_ends_with($this->visibility_statute_mi, '+');
    }

    public function ceilingAtAbove(int $feet)
    {
        // OVX is the cache XML's cover value for obscured sky / vertical visibility
        foreach ($this->sky_condition ?? [] as $layer) {
            if (in_array($layer['cover'] ?? null, ['BKN', 'OVC', 'VV', 'OVX']) && isset($layer['base_ft_agl']) && $layer['base_ft_agl'] <= $feet) {
                return true;
            }
        }

        return false;
    }

    public function foggy()
    {
        return $this->wx_string !== null && preg_match('/(FG|HZ)/', $this->wx_string) === 1;
    }

    public function heavyRain()
    {
        return $this->wx_string !== null && preg_match('/(\+RA|\+SHRA)/', $this->wx_string) === 1;
    }

    public function heavySnow()
    {
        return $this->wx_string !== null && preg_match('/(\+SN)/', $this->wx_string) === 1;
    }

    public function thunderstorm()
    {
        return $this->wx_string !== null && preg_match('/(TS|\+TSRA)/', $this->wx_string) === 1;
    }
}
