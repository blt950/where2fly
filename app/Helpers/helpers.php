<?php

use App\Helpers\CountryHelper;

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
    function distance($lat1, $lon1, $lat2, $lon2, $unit)
    {

        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $unit = strtoupper($unit);

        if ($unit == 'K') {
            return $miles * 1.609344;
        } elseif ($unit == 'N') {
            return $miles * 0.8684;
        } else {
            return $miles;
        }
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
