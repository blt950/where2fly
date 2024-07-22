<?php

namespace App\Helpers;

class AirportFilterHelper{

    public static function hasCorrectMetcon($metcon = null, $airport){
        if($metcon == "VFR" && !$airport->hasVisualCondition()) return false;
        if($metcon == "IFR" && $airport->hasVisualCondition()) return false;
        return true;
    }

    public static function hasRequiredRunwayLength($runwayLengthMin, $runwayLengthMax, $codeletter, $airport){
        $longestAirportRunway = $airport->longestRunway();
        if($longestAirportRunway < $runwayLengthMin || $longestAirportRunway > $runwayLengthMax) {
            return false;
        }
        return $airport->supportsAircraftCode($codeletter);
    }

    public static function hasRequiredAirportElevation($airportElevationMin, $airportElevationMax, $airport){
        if($airportElevationMin === null || $airportElevationMax === null) return true;

        if($airport->elevation_ft < $airportElevationMin || $airport->elevation_ft > $airportElevationMax) return false;

        return true;
    }

}