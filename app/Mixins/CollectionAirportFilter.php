<?php

namespace App\Mixins;

use App\Helpers\CalculationHelper;
use App\Helpers\AirportFilterHelper;

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
                ->filter(fn ($a) => AirportFilterHelper::hasRequiredRunwayLength($runwayLengthMin, $runwayLengthMax, $a))
                ->filter(fn ($a) => AirportFilterHelper::hasRequiredAirportElevation($airportElevationMin, $airportElevationMax, $a));

            return $returnCollection;

        };

    }

}