<?php

namespace App\Mixins;

use App\Helpers\CalculationHelper;
use App\Helpers\AirportFilterHelper;
use App\Models\Airport;
use App\Models\Airline;
use App\Models\Flight;

class CollectionAirportFilter{

    public function sortByFilteredScores(){
        return function($filteredScores){

            $result = $this->sort(function($a, $b) use ($filteredScores){

                $aScore = $a->scores->whereIn('reason', $filteredScores)->count();
                $bScore = $b->scores->whereIn('reason', $filteredScores)->count();

                if($aScore == $bScore) return 0;
                return ($aScore > $bScore) ? -1 : 1;

            });

            return $result;
        };
    }


    public function filterWithCriteria(){
        return function($departureAirport, $codeletter, $airtimeMin, $airtimeMax, $requiredMetcon = null, $requiredScores = null, $runwayLengthMin = null, $runwayLengthMax = null, $airportElevationMin = null, $airportElevationMax = null ){

            $returnCollection = $this
                ->transform(function ($arrivalAirport) use ($departureAirport, $codeletter){
                    // Insert the calculated distance and airtime into the collection
                    $distance = distance($departureAirport->latitude_deg, $departureAirport->longitude_deg, $arrivalAirport->latitude_deg, $arrivalAirport->longitude_deg, "N");
                    $arrivalAirport->distance = round($distance);

                    $airtime = ($distance / CalculationHelper::aircraftNmPerHour($codeletter)) + CalculationHelper::timeClimbDescend($codeletter);
                    $arrivalAirport->airtime = round($airtime, 1);

                    return $arrivalAirport;
                })
                ->filter(fn ($a) => AirportFilterHelper::hasCorrectMetcon($requiredMetcon, $a))
                ->filter(fn ($a) => AirportFilterHelper::hasRequiredScores($requiredScores, $a))
                ->filter(fn ($a) => AirportFilterHelper::hasRequiredAirtime($departureAirport, $a, $codeletter, $airtimeMin, $airtimeMax))
                ->filter(fn ($a) => AirportFilterHelper::hasRequiredRunwayLength($runwayLengthMin, $runwayLengthMax, $codeletter, $a))
                ->filter(fn ($a) => AirportFilterHelper::hasRequiredAirportElevation($airportElevationMin, $airportElevationMax, $a));

            return $returnCollection;

        };

    }

    public function addFlights(){
        return function(Airport $departure){

            // Get flights and airlines for the suggested airports
            $flights = Flight::where('seen_counter', '>', 3)->where('airport_dep_id', $departure->id)->whereIn('airport_arr_id', $this->pluck('id'))->get();
            $airlines = Airline::whereIn('icao_code', $flights->pluck('airline_icao')->unique())->get();

            // Loop through the suggested airports and airlines and the flights of the airlines
            foreach($this as $suggestedAirport){

                // Get flights and airlines specific for this airport
                $airportFlights = $flights->where('airport_arr_id', $suggestedAirport->id);
                $suggestedAirport->airlines = $airlines->whereIn('icao_code', $airportFlights->pluck('airline_icao')->unique());

                // Insert the flights under the airlines
                foreach($suggestedAirport->airlines as $airline){
                    $airline->flights = $airportFlights->where('airline_icao', $airline->icao_code)->sortByDesc('last_seen_at');
                }

                // Replace * with '' in all airline iata codes
                $suggestedAirport->airlines = $suggestedAirport->airlines->map(function($airline){
                    if($airline->iata_code){
                        $airline->iata_code = str_replace('*', '', $airline->iata_code);
                    }
                    return $airline;
                });

            }

            return $this;

        };  
    }

}