<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Airport;

class SearchController extends Controller{
    
    public function search(Request $request){
        
        $data = request()->validate([
            'departure' => 'required|exists:App\Models\Airport,icao',
            'continent' => 'sometimes|string',
            'codeletter' => 'required|string',
            'rwyLengthMin' => 'sometimes|between:0,16000',
            'rwyLengthMax' => 'sometimes|between:0,16000',
            'airtimeMin' => 'sometimes|between:0,24',
            'airtimeMax' => 'sometimes|between:0,24',
            'elevationMin' => 'sometimes|between:-2000,18000',
            'elevationMax' => 'sometimes|between:-2000,18000',
            'scores' => 'sometimes|array',
            'metcondition' => 'sometimes|in:IFR,VFR',
            'arrivalWhitelist' => 'sometimes|array',
            'limit' => 'sometimes|integer|between:1,30',
            //'onlyAirportsWithScore' => 'sometimes|boolean'
        ]);

        isset($data['continent']) ? $continent = $data['continent'] : $continent = null;
        $codeletter = $data['codeletter'];
        isset($data['rwyLengthMin']) ? $rwyLengthMin = $data['rwyLengthMin'] : $rwyLengthMin = 0;
        isset($data['rwyLengthMax']) ? $rwyLengthMax = $data['rwyLengthMax'] : $rwyLengthMax = 16000;
        isset($data['airtimeMin']) ? $airtimeMin = $data['airtimeMin'] : $airtimeMin = 0;
        isset($data['airtimeMax']) ? $airtimeMax = $data['airtimeMax'] : $airtimeMax = 24;
        isset($data['elevationMin']) ? $elevationMin = $data['elevationMin'] : $elevationMin = -2000;
        isset($data['elevationMax']) ? $elevationMax = $data['elevationMax'] : $elevationMax = 18000;
        isset($data['scores']) ? $filteredScores = $data['scores'] : $filteredScores = null;
        isset($data['metcondition']) ? $metcon = $data['metcondition'] : $metcon = null;
        isset($data['arrivalWhitelist']) ? $arrivalWhitelist = $data['arrivalWhitelist'] : $arrivalWhitelist = null;
        isset($data['limit']) ? $resultLimit = $data['limit'] : $resultLimit = 10;
        //(isset($data['onlyAirportsWithScore']) && (boolean)$data['onlyAirportsWithScore']) ? $onlyAirportsWithScore = true : $onlyAirportsWithScore = false;

        $departure = Airport::where('icao', $data['departure'])->get()->first();

        $airports = collect();
        $airports = Airport::findWithCriteria($continent, $departure->iso_country, $departure->icao, $arrivalWhitelist);

        $suggestedAirports = $airports->filterWithCriteria($departure, $codeletter, $airtimeMin, $airtimeMax, $metcon, $filteredScores, $rwyLengthMin, $rwyLengthMax, $elevationMin, $elevationMax);
        $suggestedAirports = $suggestedAirports->shuffle(); 
        
        // @TODO: Implement once strict search is availble. If requested result with only scored airports
        /*if($onlyAirportsWithScore){
            $suggestedAirports = $suggestedAirports->filter(function($airport){
                return $airport->scores->count() == 0;
            });
        }*/
        
        $suggestedAirports = $suggestedAirports->sortByFilteredScores($filteredScores);
        $suggestedAirports = $suggestedAirports->splice(0,$resultLimit);

        // Prepare arrival suggestions
        $arrivalData = collect();
        foreach($suggestedAirports as $airport){

            $scores = $airport->scores->pluck('reason');

            $arrivalData->push([

                "name" => $airport->name,
                "icao" => $airport->icao,
                "iata" => $airport->iata_code ? $airport->iata_code : null,
                "contient" => $airport->continent,
                "country" => $airport->iso_country,
                "region" => $airport->iso_region,
                "metar" => $airport->metar->metar,
                "longestRwyFt" => $airport->longestRunway(),
                "scores" => $scores,
                "airtime" => $airport->airtime,
                "distanceNm" => $airport->distance,
                "isAirforcebase" => $airport->w2f_airforcebase,
                "hasAirlineService" => $airport->w2f_scheduled_service,
            ]);
        }

        // Prepare departure data
        $departureData = [
            "name" => $departure->name,
            "icao" => $departure->icao,
            "iata" => $departure->iata_code ? $departure->iata_code : null,
            "contient" => $departure->continent,
            "country" => $departure->iso_country,
            "region" => $departure->iso_region,
            "metar" => $departure->metar->metar,
            "longestRwyFt" => $departure->longestRunway(),
            "scores" => $departure->scores->pluck('reason'),
        ];

        // Send the response
        return response()->json([
            'message' => 'Success',
            'data' => [
                "departure" => $departureData,
                "arrivals" => $arrivalData
            ]
        ], 200);

    }

}