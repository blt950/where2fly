<?php

namespace App\Mixins;

use App\Helpers\AirportFilterHelper;
use App\Helpers\CalculationHelper;

class CollectionAirportFilter
{
    public function filterWithCriteria()
    {
        return function ($departureAirport, $codeletter, $airtimeMin, $airtimeMax, $requiredMetcon = null, $runwayLengthMin = null, $runwayLengthMax = null, $airportElevationMin = null, $airportElevationMax = null) {

            $returnCollection = $this
                ->transform(function ($arrivalAirport) use ($departureAirport, $codeletter) {
                    // Insert the calculated distance and airtime into the collection
                    $distance = distance($departureAirport->latitude_deg, $departureAirport->longitude_deg, $arrivalAirport->latitude_deg, $arrivalAirport->longitude_deg, 'N');
                    $arrivalAirport->distance = round($distance);

                    $airtime = ($distance / CalculationHelper::aircraftNmPerHour($codeletter)) + CalculationHelper::timeClimbDescend($codeletter);
                    $arrivalAirport->airtime = round($airtime, 1);

                    return $arrivalAirport;
                })
                ->filter(fn ($a) => AirportFilterHelper::hasCorrectMetcon($requiredMetcon, $a))
                ->filter(fn ($a) => AirportFilterHelper::hasRequiredAirportElevation($airportElevationMin, $airportElevationMax, $a));

            return $returnCollection;

        };

    }
}
