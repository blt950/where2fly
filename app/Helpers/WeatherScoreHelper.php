<?php

namespace App\Helpers;

use App\Models\Metar;
use App\Models\TafForecast;

class WeatherScoreHelper
{
    /**
     * The phenomenon classification rules, shared by Metar (matching raw METAR
     * text) and TafForecast (matching the cache XML's wx_string) so the same
     * weather always scores the same whether it's an observation or a forecast.
     */
    public const FOG_PATTERN = '/(FG|HZ)/';

    public const HEAVY_RAIN_PATTERN = '/(\+RA|\+SHRA)/';

    public const HEAVY_SNOW_PATTERN = '/(\+SN)/';

    public const THUNDERSTORM_PATTERN = '/(TS|\+TSRA)/';

    public const METERS_PER_STATUTE_MILE = 1609.344;

    /**
     * Weather score reasons derivable from either a current observation (Metar)
     * or a forecast period (TafForecast) — both expose the same condition
     * methods. The runway-dependent reasons (METAR_RVR, METAR_CROSSWIND) stay
     * in fetch:metars: they need runway data, and RVR isn't forecast in TAFs at all.
     *
     * @return array<string>
     */
    public static function reasons(Metar|TafForecast $weather): array
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
