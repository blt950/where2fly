<?php

namespace App\Mixins;

use App\Helpers\CalculationHelper;
use App\Helpers\AirportFilterHelper;
use App\Models\Airport;
use App\Models\Airline;
use App\Models\Flight;

class CollectionAirportFilter{

    public function sortByScores(){
        return function($sortByScores){

            $result = $this->sort(function($a, $b) use ($sortByScores){

                $aScore = $a->scores->whereIn('reason', $sortByScores)->count();
                $bScore = $b->scores->whereIn('reason', $sortByScores)->count();

                if($aScore == $bScore) return 0;
                return ($aScore > $bScore) ? -1 : 1;

            });

            return $result;
        };
    }


    public function filterWithCriteria(){
        return function($departureAirport, $codeletter, $airtimeMin, $airtimeMax, $requiredMetcon = null, $runwayLengthMin = null, $runwayLengthMax = null, $airportElevationMin = null, $airportElevationMax = null ){

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
                ->filter(fn ($a) => AirportFilterHelper::hasRequiredAirtime($departureAirport, $a, $codeletter, $airtimeMin, $airtimeMax))
                ->filter(fn ($a) => AirportFilterHelper::hasRequiredRunwayLength($runwayLengthMin, $runwayLengthMax, $codeletter, $a))
                ->filter(fn ($a) => AirportFilterHelper::hasRequiredAirportElevation($airportElevationMin, $airportElevationMax, $a));

            return $returnCollection;

        };

    }

    public function addFlights(){
        return function(Airport $departure){

            // Get flights and airlines for the suggested airports
            $flights = Flight::where('seen_counter', '>', 3)->where('airport_dep_id', $departure->id)->whereIn('airport_arr_id', $this->pluck('id'))->with('aircrafts')->orderBy('last_seen_at')->get();
            $airlines = Airline::whereIn('icao_code', $flights->pluck('airline_icao')->unique())->get();

            foreach($this as $arrivalAirport){
                $arrivalAirport->flights = $flights->where('airport_arr_id', $arrivalAirport->id);
                $arrivalAirport->airlines = $airlines->whereIn('icao_code', $arrivalAirport->flights->pluck('airline_icao')->unique());

                // Replace * with '' in all airline iata codes
                foreach($arrivalAirport->airlines as $airline){
                    $airline->iata_code = str_replace('*', '', $airline->iata_code);
                }
            }

            return $this;

        };  
    }

}