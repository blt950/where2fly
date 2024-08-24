<?php

namespace App\Mixins;

use App\Helpers\AirportFilterHelper;
use App\Helpers\CalculationHelper;
use App\Models\Airline;
use App\Models\Airport;
use App\Models\Flight;

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

    public function addFlights()
    {
        return function (Airport $airport, $direction) {

            $arrivalAirportColumn = $direction === 'departure' ? 'airport_dep_id' : 'airport_arr_id';
            $departureAirportColumn = $direction === 'departure' ? 'airport_arr_id' : 'airport_dep_id';

            // Get flights and airlines for the suggested airports
            $flights = Flight::where('seen_counter', '>', 3)->where($arrivalAirportColumn, $airport->id)->whereIn($departureAirportColumn, $this->pluck('id'))->with('aircrafts')->orderBy('last_seen_at')->get();
            $airlines = Airline::whereIn('icao_code', $flights->pluck('airline_icao')->unique())->get();

            foreach ($this as $airport) {
                $airport->flights = $flights->where($departureAirportColumn, $airport->id);
                $airport->airlines = $airlines->whereIn('icao_code', $airport->flights->pluck('airline_icao')->unique());

                // Replace * with '' in all airline iata codes
                foreach ($airport->airlines as $airline) {
                    $airline->iata_code = str_replace('*', '', $airline->iata_code);
                }
            }

            return $this;

        };
    }
}
