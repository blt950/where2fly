<?php

namespace App\Helpers;
use InvalidArgumentException;
use Location\Coordinate;

class CalculationHelper{

    /**
     *  Calculate aircraft travel nautrical miles per hour
     *
     * @param  string $actCode Aircraft code
     * @return int Cruice speed
     */
    public static function aircraftNmPerHour(string $actCode){

        $crzSpeed = 0;
        switch($actCode){
            case "A":
                $crzSpeed = 115;
                break;
            case "B":
                $crzSpeed = 360;
                break;
            case "C":
                $crzSpeed = 460;
                break;
            case "D":
                $crzSpeed = 480;
                break;
            case "E":
                $crzSpeed = 510;
                break;
            case "F":
                $crzSpeed = 520;
                break;
            default:
                $crzSpeed = 0;
        }

        return $crzSpeed;
    }

    /**
     *  Calculate minute addition for climbing the aircraft
     *
     * @param  string $actCode Aircraft code
     * @return int Additional minutes
     */
    public static function timeClimbDescend(string $actCode){
        
        $addMinutes = 0;
        switch($actCode){
            case "A":
                $addMinutes = 0.35;
                break;
            case "B":
                $addMinutes = 0.35;
                break;
            case "C":
                $addMinutes = 0.5;
                break;
            case "D":
                $addMinutes = 0.5;
                break;
            case "E":
                $addMinutes = 0.5;
                break;
            case "F":
                $addMinutes = 0.5;
                break;
            default:
                $addMinutes = 0;
        }

        return $addMinutes;

    }

    /** 
     * Calculate the nautical miles the selected aircraft type will fly in an hour
     * 
     * @param string $actCode Aircraft code
     * @param int $minHours Minimum hours
     * @param int $maxHours Maximum hours
     * @return int Nautical miles
     */

    public static function aircraftNmPerHourRange(string $actCode, int $minHours, int $maxHours){
        $minDistance = self::aircraftNmPerHour($actCode);
        $maxDistance = self::aircraftNmPerHour($actCode);

        // Convert to nm and multiply by hours
        $minDistance = ($minDistance)  * $minHours;
        $maxDistance = ($maxDistance) * $maxHours;

        if($minDistance !== 0){
            $minDistance += self::timeClimbDescend($actCode);
        }

        return [$minDistance, $maxDistance];
    }

    /**
     * Calculates a destination point for the given point, bearing angle,
     * and distance.
     *
     * @param float $bearing the bearing angle between 0 and 360 degrees
     * @param float $distance the distance to the destination point in meters
     *
     * @throws InvalidArgumentException
     */
    public static function calculateSphericalDestination(Coordinate $point, float $bearing, float $distance): Coordinate
    {
        $D = $distance / 6371009.0;
        $B = deg2rad($bearing);
        $φ = deg2rad($point->getLat());
        $λ = deg2rad($point->getLng());

        $Φ = asin(sin($φ) * cos($D) + cos($φ) * sin($D) * cos($B));
        $Λ = $λ + atan2(sin($B) * sin($D) * cos($φ), cos($D) - sin($φ) * sin($φ));

        $Φ = rad2deg($Φ);
        $Λ = rad2deg($Λ);

        if($Φ > 90) $Φ = 90;
        if($Φ < -90) $Φ = -90;
        
        if($Λ > 180) $Λ = 180;
        if($Λ < -180) $Λ = -180;

        return new Coordinate($Φ, $Λ);
    }

    /**
     * Normalize longitude to ensure it falls within the range of -180 to +180 degrees.
     *
     * @param float $longitude
     * @return float
     */
    public static function normalizeLongitude(float $longitude): float
    {
        // Normalize the longitude to be within the range of -180 to +180
        while ($longitude < -180) {
            $longitude += 360;
        }
        
        while ($longitude > 180) {
            $longitude -= 360;
        }

        return $longitude;
    }

}