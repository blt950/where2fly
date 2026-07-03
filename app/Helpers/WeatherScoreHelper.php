<?php

namespace App\Helpers;

use App\Models\Metar;
use App\Models\Taf;

class WeatherScoreHelper
{
    /**
     * Weather score reasons derivable from either a current observation (Metar)
     * or a forecast period (Taf) — both expose the same condition methods. The
     * runway-dependent reasons (METAR_RVR, METAR_CROSSWIND) stay in calc:scores:
     * they need runway data, and RVR isn't forecast in TAFs at all.
     *
     * @return array<string>
     */
    public static function reasons(Metar|Taf $weather): array
    {
        $reasons = [];

        if ($weather->windAtAbove(15)) {
            $reasons[] = 'METAR_WINDY';
        }

        if ($weather->windGusts()) {
            $reasons[] = 'METAR_GUSTS';
        }

        if ($weather->sightBelow(5000)) {
            $reasons[] = 'METAR_SIGHT';
        }

        if ($weather->ceilingAtAbove(300)) {
            $reasons[] = 'METAR_CEILING';
        }

        if ($weather->foggy()) {
            $reasons[] = 'METAR_FOGGY';
        }

        if ($weather->heavyRain()) {
            $reasons[] = 'METAR_HEAVY_RAIN';
        }

        if ($weather->heavySnow()) {
            $reasons[] = 'METAR_HEAVY_SNOW';
        }

        if ($weather->thunderstorm()) {
            $reasons[] = 'METAR_THUNDERSTORM';
        }

        return $reasons;
    }
}
