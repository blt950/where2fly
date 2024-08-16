<?php

namespace App\Helpers;

use InvalidArgumentException;
use Location\Coordinate;

class CalculationHelper
{
    /**
     * Calculate the distance between two points
     *
     * $param string $code aircraft type code
     *
     * @return int Required runway length
     */
    public static function minimumRequiredRunwayLength(string $code)
    {
        switch ($code) {
            case 'A':
                return 100;
            case 'B':
                return 1400;
            case 'C':
                return 5600;
            case 'D':
                return 6500;
            case 'E':
                return 7500;
            case 'F':
                return 8000;
            default:
                return 0;
        }
    }

    /**
     *  Calculate aircraft travel nautrical miles per hour
     *
     * @param  string  $actCode  Aircraft code
     * @return int Cruice speed
     */
    public static function aircraftNmPerHour(string $actCode)
    {

        $crzSpeed = 0;
        switch ($actCode) {
            case 'A':
                $crzSpeed = 115;
                break;
            case 'B':
                $crzSpeed = 360;
                break;
            case 'C':
                $crzSpeed = 460;
                break;
            case 'D':
                $crzSpeed = 480;
                break;
            case 'E':
                $crzSpeed = 510;
                break;
            case 'F':
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
     * @param  string  $actCode  Aircraft code
     * @return int Additional minutes
     */
    public static function timeClimbDescend(string $actCode)
    {

        $addMinutes = 0;
        switch ($actCode) {
            case 'A':
                $addMinutes = 0.35;
                break;
            case 'B':
                $addMinutes = 0.35;
                break;
            case 'C':
                $addMinutes = 0.5;
                break;
            case 'D':
                $addMinutes = 0.5;
                break;
            case 'E':
                $addMinutes = 0.5;
                break;
            case 'F':
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
     * @param  string  $actCode  Aircraft code
     * @param  int  $minHours  Minimum hours
     * @param  int  $maxHours  Maximum hours
     * @return int Nautical miles
     */
    public static function aircraftNmPerHourRange(string $actCode, int $minHours, int $maxHours)
    {
        $minDistance = self::aircraftNmPerHour($actCode);
        $maxDistance = self::aircraftNmPerHour($actCode);

        // Convert to nm and multiply by hours
        $minDistance = ($minDistance) * $minHours;
        $maxDistance = ($maxDistance) * $maxHours;

        if ($minDistance !== 0) {
            $minDistance += self::timeClimbDescend($actCode);
        }

        return [$minDistance, $maxDistance];
    }

    /**
     * Calculates a destination point for the given point, bearing angle,
     * and distance.
     *
     * @param  float  $bearing  the bearing angle between 0 and 360 degrees
     * @param  float  $distance  the distance to the destination point in meters
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

        if ($Φ > 90) {
            $Φ = 90;
        }
        if ($Φ < -90) {
            $Φ = -90;
        }

        if ($Λ > 180) {
            $Λ = 180;
        }
        if ($Λ < -180) {
            $Λ = -180;
        }

        return new Coordinate($Φ, $Λ);
    }
}
