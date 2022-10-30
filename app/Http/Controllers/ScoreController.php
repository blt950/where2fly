<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ScoreController extends Controller
{
    public static $score_types = [
        'METAR_SIGHT' => ['icon' => 'fa-eye-slash', 'desc' => 'Reduced sight'],
        'METAR_WINDY' => ['icon' => 'fa-wind', 'desc' => 'Windy'],
        'METAR_CEILING' => ['icon' => 'fa-arrows-up-to-line', 'desc' => 'Low Ceiling'],
        'METAR_GUSTS' => ['icon' => 'fa-arrows-turn-right', 'desc' => 'Gusting Wind'],
        'METAR_FOGGY' => ['icon' => 'fa-smog', 'desc' => 'Foggy'],
        'METAR_HEAVY_RAIN' => ['icon' => 'fa-cloud-showers-heavy', 'desc' => 'Heavy Rain'],
        'METAR_HEAVY_SNOW' => ['icon' => 'fa-snowflake', 'desc' => 'Heavy Snow'],
        'METAR_THUNDERSTORM' => ['icon' => 'fa-cloud-bolt', 'desc' => 'Thunderstorm'],
        'METAR_RVR' => ['icon' => 'fa-arrows-left-right', 'desc' => 'Runway Visual Range'],
        'METAR_CROSSWIND' => ['icon' => 'fa-xmark', 'desc' => 'Crosswind'],
        'VATSIM_ATC' => ['icon' => 'fa-tower-cell', 'desc' => 'VATSIM ATC Online'],
        'VATSIM_EVENT' => ['icon' => 'fa-calendar', 'desc' => 'VATSIM Event Ongoing'],
        'VATSIM_POPULAR' => ['icon' => 'fa-fire', 'desc' => 'VATSIM Popular Airport'],
    ];

    
    
}