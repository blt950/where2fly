<?php

namespace App\Helpers;

class AirportFilterHelper{

    public static function hasCorrectMetcon($metcon = null, $airport){
        if($metcon == "VFR" && !$airport->hasVisualCondition()) return false;
        if($metcon == "IFR" && $airport->hasVisualCondition()) return false;
        return true;
    }

    public static function hasRequiredScores($requiredScores = null, $airport){
        if($requiredScores == null) return true;

        if($airport->scores->whereIn('reason', $requiredScores)->count() == 0) return false;
        return true;
    }

    public static function hasRequiredAirtime($departureAirport, $arrivalAirport, $codeletter, $airtimeMin, $airtimeMax){
        $distance = distance($departureAirport->latitude_deg, $departureAirport->longitude_deg, $arrivalAirport->latitude_deg, $arrivalAirport->longitude_deg, "N");
        $estimatedAirtime = ($distance / CalculationHelper::aircraftNmPerHour($codeletter)) + CalculationHelper::timeClimbDescend($codeletter);

        if($estimatedAirtime < $airtimeMin || $estimatedAirtime > $airtimeMax) return false;
        return true;
    }

    public static function hasRequiredRunwayLength($runwayLengthMin, $runwayLengthMax, $codeletter, $airport){

        // If min/max is not defined it's a simple search and should just match according to codeletter
        if($runwayLengthMin === null || $runwayLengthMax === null){
            return $airport->supportsAircraftCode($codeletter);
        }

        $longestAirportRunway = $airport->longestRunway();
        if($longestAirportRunway < $runwayLengthMin || $longestAirportRunway > $runwayLengthMax) {
            
            return false;
        }
        return true;
    }

    public static function hasRequiredAirportElevation($airportElevationMin, $airportElevationMax, $airport){
        if($airportElevationMin === null || $airportElevationMax === null) return true;

        if($airport->elevation_ft < $airportElevationMin || $airport->elevation_ft > $airportElevationMax) return false;

        return true;
    }

}