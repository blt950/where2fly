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
        return match ($code) {
            'GA' => 100,
            'GAT' => 2000,
            'GTP' => 2500,
            'JS' => 4000,
            'JM' => 5000,
            'JML' => 6000,
            'JL' => 7000,
            'JXL' => 8000,
            default => 0,
        };
    }

    /**
     *  Calculate aircraft travel nautrical miles per hour
     *
     * @param  string  $actCode  Aircraft code
     * @return int Cruice speed
     */
    public static function aircraftNmPerHour(string $actCode)
    {

        return match ($actCode) {
            'GA' => 115,
            'GAT' => 190,
            'GTP' => 280,
            'JS' => 340,
            'JM' => 460,
            'JML' => 480,
            'JL' => 510,
            'JXL' => 520,
            default => 0,
        };
    }

    /**
     *  Calculate minute addition for climbing the aircraft
     *
     * @param  string  $actCode  Aircraft code
     * @return int Additional minutes
     */
    public static function timeClimbDescend(string $actCode)
    {

        return match ($actCode) {
            'GA' => 0.13,
            'GAT' => 0.20,
            'GTP' => 0.25,
            'JS' => 0.33,
            'JM' => 0.42,
            'JML' => 0.47,
            'JL' => 0.50,
            'JXL' => 0.58,
            default => 0,
        };

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
        $speed = self::aircraftNmPerHour($actCode);

        // Convert to nm and multiply by hours
        $minDistance = $speed * $minHours;
        $maxDistance = $speed * $maxHours;

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

        $Φ = max(-90.0, min(90.0, $Φ));
        $Λ = max(-180.0, min(180.0, $Λ));

        return new Coordinate($Φ, $Λ);
    }
}
