<?php

namespace App\Helpers;

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
        $minDistance = ($minDistance * 1852)  * $minHours;
        $maxDistance = ($maxDistance * 1852) * $maxHours;

        if($minDistance !== 0){
            $minDistance += self::timeClimbDescend($actCode);
        }

        return [$minDistance, $maxDistance];
    }

}