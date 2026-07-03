<?php

namespace App\Mixins;

use App\Helpers\AirportFilterHelper;
use App\Helpers\CalculationHelper;

class CollectionAirportFilter
{
    public function filterWithCriteria()
    {
        return function ($departureAirport, $codeletter, $requiredMetcon = null, $temperatureMin = null, $temperatureMax = null, $airportElevationMin = null, $airportElevationMax = null) {

            return $this
                ->transform(function ($arrivalAirport) use ($departureAirport, $codeletter) {
                    // Insert the calculated distance and airtime into the collection
                    $distance = distance($departureAirport->latitude_deg, $departureAirport->longitude_deg, $arrivalAirport->latitude_deg, $arrivalAirport->longitude_deg, 'N');
                    $arrivalAirport->distance = round($distance);

                    $airtime = ($distance / CalculationHelper::aircraftNmPerHour($codeletter)) + CalculationHelper::timeClimbDescend($codeletter);
                    $arrivalAirport->airtime = round($airtime, 1);

                    // Narrow the loaded scores to those applicable at this candidate's
                    // ETA, and flag whether they're TAF-backed or a METAR fallback
                    if ($arrivalAirport->relationLoaded('scores')) {
                        [$scores, $hasTafAtEta] = $arrivalAirport->scoresAtEta(CalculationHelper::forecastEta($arrivalAirport->airtime));
                        $arrivalAirport->setRelation('scores', $scores);
                        $arrivalAirport->forecast_source = $hasTafAtEta ? 'taf' : 'metar_fallback';
                    }

                    return $arrivalAirport;
                })
                ->filter(fn ($a) => AirportFilterHelper::hasCorrectMetcon($requiredMetcon, $a))
                ->filter(fn ($a) => AirportFilterHelper::hasRequiredTemperature($temperatureMin, $temperatureMax, $a))
                ->filter(fn ($a) => AirportFilterHelper::hasRequiredAirportElevation($airportElevationMin, $airportElevationMax, $a));

        };

    }
}
