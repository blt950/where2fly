<?php

use App\Helpers\CountryHelper;
use Location\Coordinate;
use Location\Distance\Haversine;

if (! function_exists('correctHeading')) {
    function correctHeading(float $heading)
    {
        if ($heading > 360.0) {
            $heading = $heading - 360;
        } elseif ($heading < 1.0) {
            $heading = 360 - abs($heading);
        }

        return $heading;
    }
}

if (! function_exists('getCountryName')) {
    function getCountryName($countryCode)
    {
        return CountryHelper::name($countryCode);
    }
}

if (! function_exists('distance')) {
    /**
     * Great-circle distance between two points in nautical miles
     */
    function distance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        return (new Haversine)->getDistance(new Coordinate($lat1, $lon1), new Coordinate($lat2, $lon2)) / 1852;
    }
}

if (! function_exists('rwyIdentToHeading')) {

    function rwyIdentToHeading($ident)
    {
        $heading = $ident;
        if (strlen($heading) == 2) {
            $heading .= '0';
        }

        return floatval($heading);
    }

}

if (! function_exists('getRussianAsianRegions')) {

    function getRussianAsianRegions()
    {
        return [
            'RU-KAM',
            'RU-CHU',
            'RU-MAG',
            'RU-SAK',
            'RU-PRI',
            'RU-KHA',
            'RU-YEV',
            'RU-AMU',
            'RU-SA',
            'RU-IRK',
            'RU-ZAB',
            'RU-BU',
            'RU-KYA',
            'RU-TY',
            'RU-ALT',
            'RU-AL',
            'RU-KEM',
            'RU-KK',
            'RU-NVS',
            'RU-TOM',
            'RU-KHM',
            'RU-YAN',
            'RU-OMS',
            'RU-TYU',
            'RU-KGN',
            'RU-SVE',
            'RU-CHE',
        ];
    }
}
