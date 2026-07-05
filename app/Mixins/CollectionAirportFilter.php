<?php

namespace App\Mixins;

use App\Helpers\AirportFilterHelper;
use App\Helpers\CalculationHelper;

class CollectionAirportFilter
{
    public function filterWithCriteria()
    {
        return function ($departureAirport, $codeletter, $requiredMetcon = null, $temperatureMin = null, $temperatureMax = null, $airportElevationMin = null, $airportElevationMax = null, $candidatesAreDepartures = false) {

            return $this
                ->transform(function ($arrivalAirport) use ($departureAirport, $codeletter, $candidatesAreDepartures) {
                    // Insert the calculated distance and airtime into the collection
                    $distance = distance($departureAirport->latitude_deg, $departureAirport->longitude_deg, $arrivalAirport->latitude_deg, $arrivalAirport->longitude_deg, 'N');
                    $arrivalAirport->distance = round($distance);

                    $airtime = CalculationHelper::airtimeHours($distance, $codeletter);
                    $arrivalAirport->airtime = round($airtime, 1);

                    // Narrow down loaded sources to those who are applicable at the time of arrival.
                    if ($arrivalAirport->relationLoaded('scores')) {
                        $eta = $candidatesAreDepartures ? now() : CalculationHelper::forecastEta($arrivalAirport->airtime);
                        [$scores, $hasTafAtEta] = $arrivalAirport->scoresAtEta($eta, $candidatesAreDepartures);
                        $arrivalAirport->setRelation('scores', $scores);
                        $arrivalAirport->forecast_source = ! $candidatesAreDepartures && $hasTafAtEta ? 'taf' : 'metar';
                    }

                    return $arrivalAirport;
                })
                ->filter(fn ($a) => AirportFilterHelper::hasCorrectMetcon($requiredMetcon, $a))
                ->filter(fn ($a) => AirportFilterHelper::hasRequiredTemperature($temperatureMin, $temperatureMax, $a))
                ->filter(fn ($a) => AirportFilterHelper::hasRequiredAirportElevation($airportElevationMin, $airportElevationMax, $a));

        };

    }
}
