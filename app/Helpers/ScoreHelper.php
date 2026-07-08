<?php

namespace App\Helpers;

class ScoreHelper
{
    /** All score reasons with their display icon and description */
    public const TYPES = [
        'METAR_WINDY' => ['icon' => 'fa-windsock', 'desc' => 'Windy'],
        'METAR_GUSTS' => ['icon' => 'fa-wind', 'desc' => 'Gusting Wind'],
        'METAR_CROSSWIND' => ['icon' => 'fa-arrows-cross', 'desc' => 'Crosswind'],

        'METAR_SIGHT' => ['icon' => 'fa-eye-low-vision', 'desc' => 'Reduced Sight'],
        'METAR_RVR' => ['icon' => 'fa-arrows-left-right-to-line', 'desc' => 'Runway Visual Range'],
        'METAR_CEILING' => ['icon' => 'fa-arrows-up-to-line', 'desc' => 'Low Ceiling'],

        'METAR_FOGGY' => ['icon' => 'fa-smog', 'desc' => 'Foggy'],
        'METAR_HEAVY_RAIN' => ['icon' => 'fa-cloud-showers-heavy', 'desc' => 'Heavy Rain'],
        'METAR_HEAVY_SNOW' => ['icon' => 'fa-cloud-snow', 'desc' => 'Heavy Snow'],
        'METAR_THUNDERSTORM' => ['icon' => 'fa-cloud-bolt', 'desc' => 'Thunderstorm'],

        'VATSIM_ATC' => ['icon' => 'fa-tower-control', 'desc' => 'VATSIM ATC'],
        'VATSIM_EVENT' => ['icon' => 'fa-calendar', 'desc' => 'VATSIM Event'],
        'VATSIM_POPULAR' => ['icon' => 'fa-fire', 'desc' => 'VATSIM Popular Airport'],
    ];

    public static function weatherTypes(): array
    {
        return self::typesWithPrefix('METAR_');
    }

    public static function vatsimTypes(): array
    {
        return self::typesWithPrefix('VATSIM_');
    }

    private static function typesWithPrefix(string $prefix): array
    {
        return array_values(array_filter(array_keys(self::TYPES), fn ($key) => str_starts_with($key, $prefix)));
    }
}
