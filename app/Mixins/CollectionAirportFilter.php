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

                    $airtime = ($distance / CalculationHelper::aircraftNmPerHour($codeletter)) + CalculationHelper::timeClimbDescend($codeletter);
                    $arrivalAirport->airtime = round($airtime, 1);

                    // Narrow the loaded scores to those applicable when the pilot is
                    // at this candidate: its ETA for arrival suggestions, now for
                    // departure suggestions (you leave there soon — the current METAR
                    // is what matters, not a forecast)
                    if ($arrivalAirport->relationLoaded('scores')) {
                        $eta = $candidatesAreDepartures ? now() : CalculationHelper::forecastEta($arrivalAirport->airtime);
                        [$scores, $hasTafAtEta] = $arrivalAirport->scoresAtEta($eta);
                        $arrivalAirport->setRelation('scores', $scores);
                        $arrivalAirport->forecast_source = $candidatesAreDepartures ? 'metar' : ($hasTafAtEta ? 'taf' : 'metar_fallback');
                    }

                    return $arrivalAirport;
                })
                ->filter(fn ($a) => AirportFilterHelper::hasCorrectMetcon($requiredMetcon, $a))
                ->filter(fn ($a) => AirportFilterHelper::hasRequiredTemperature($temperatureMin, $temperatureMax, $a))
                ->filter(fn ($a) => AirportFilterHelper::hasRequiredAirportElevation($airportElevationMin, $airportElevationMax, $a));

        };

    }
}
