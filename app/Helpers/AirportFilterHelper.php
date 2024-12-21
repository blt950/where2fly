<?php

namespace App\Helpers;

class AirportFilterHelper
{
    public static function hasCorrectMetcon($metcon, $airport)
    {
        if ($metcon == 'VFR' && ! $airport->hasVisualCondition()) {
            return false;
        }
        if ($metcon == 'IFR' && $airport->hasVisualCondition()) {
            return false;
        }

        return true;
    }

    public static function hasRequiredAirportElevation($airportElevationMin, $airportElevationMax, $airport)
    {
        if ($airportElevationMin === null || $airportElevationMax === null) {
            return true;
        }

        if ($airport->elevation_ft < $airportElevationMin || $airport->elevation_ft > $airportElevationMax) {
            return false;
        }

        return true;
    }

    public static function hasRequiredTemperature($temperatureMin, $temperatureMax, $airport)
    {
        if ($temperatureMin === null || $temperatureMax === null) {
            return true;
        }

        if ($airport->metar->temperature < $temperatureMin || $airport->metar->temperature > $temperatureMax) {
            return false;
        }

        return true;
    }
}
