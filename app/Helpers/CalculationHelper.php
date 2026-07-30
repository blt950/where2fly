<?php

namespace App\Helpers;

use App\Models\Airport;
use Carbon\Carbon;
use InvalidArgumentException;
use Location\Coordinate;

class CalculationHelper
{
    /**
     * The ETA used to time-scope forecast lookups — never shown to the client.
     * No buffer: ETA = now + airtime.
     */
    public static function forecastEta(float $airtimeHours): Carbon
    {
        return now()->addSeconds((int) round($airtimeHours * 3600));
    }

    /**
     * The canonical airtime formula: distance at cruise speed plus a fixed
     * climb/descend allowance. Keep in sync with its SQL twin forecastEtaSql().
     */
    public static function airtimeHours(float $distanceNm, string $codeletter): float
    {
        return $distanceNm / AircraftHelper::cruiseKts($codeletter) + AircraftHelper::climbDescendHours($codeletter);
    }

    /**
     * forecastEta() as a SQL expression, since the distance to each candidate
     * airport is only known inside the query. SQL twin of airtimeHours().
     */
    public static function forecastEtaSql(Airport $anchorAirport, string $codeletter): string
    {
        $connection = $anchorAirport->getConnection();
        $anchorPoint = $anchorAirport->coordinates->toSqlExpression($connection)->getValue($connection->getQueryGrammar());

        return sprintf(
            'DATE_ADD(NOW(), INTERVAL ((ST_DISTANCE_SPHERE(airports.coordinates, %s) / 1852 / %d + %F) * 3600) SECOND)',
            $anchorPoint,
            AircraftHelper::cruiseKts($codeletter),
            AircraftHelper::climbDescendHours($codeletter)
        );
    }

    /**
     * The distance range (NM) the aircraft covers within the given airtime range
     */
    public static function aircraftNmPerHourRange(string $actCode, int $minHours, int $maxHours)
    {
        $speed = AircraftHelper::cruiseKts($actCode);

        $minDistance = $speed * $minHours;
        $maxDistance = $speed * $maxHours;

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
