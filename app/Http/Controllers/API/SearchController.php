<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Airport;

class SearchController extends Controller{
    
    public function search(Request $request){


        /**
        *
        *  Validate the request and mapping of arguments
        *
        */

        $data = request()->validate([
            'departure' => 'required|exists:App\Models\Airport,icao',
            'continent' => 'required_without:arrivalWhitelist|string',
            'codeletter' => 'required|string',
            'airtimeMin' => 'sometimes|numeric|between:0,24',
            'airtimeMax' => 'sometimes|numeric|between:0,24',
            'scores' => 'sometimes|array',
            'metcondition' => 'sometimes|in:IFR,VFR,ANY',
            'destinationWithRoutesOnly' => 'sometimes|numeric|between:-1,1',
            'destinationRunwayLights' => 'sometimes|numeric|between:-1,1',
            'destinationAirbases' => 'sometimes|numeric|between:-1,1',
            'destinationAirportSize' => 'sometimes|array',
            'elevationMin' => 'sometimes|numeric|between:-2000,18000',
            'elevationMax' => 'sometimes|numeric|between:-2000,18000',
            'rwyLengthMin' => 'sometimes|numeric|between:0,17000',
            'rwyLengthMax' => 'sometimes|numeric|between:0,17000',
            
            'arrivalWhitelist' => 'sometimes|array',
            'limit' => 'sometimes|integer|between:1,30',
        ]);

        isset($data['continent']) ? $continent = $data['continent'] : $continent = null;
        $codeletter = $data['codeletter'];
        isset($data['airtimeMin']) ? $airtimeMin = $data['airtimeMin'] : $airtimeMin = 0;
        isset($data['airtimeMax']) ? $airtimeMax = $data['airtimeMax'] : $airtimeMax = 24;
        isset($data['scores']) ? $filterByScores = array_map('intval', $data['scores']) : $filterByScores = null;
        isset($data['metcondition']) ? $metcon = $data['metcondition'] : $metcon = null;
        isset($data['destinationRunwayLights']) ? $destinationRunwayLights = (int)$data['destinationRunwayLights'] : $destinationRunwayLights = 0;
        isset($data['destinationAirbases']) ? $destinationAirbases = (int)$data['destinationAirbases'] : $destinationAirbases = 0;
        (isset($data['destinationAirportSize']) && !empty($data['destinationAirportSize'])) ? $destinationAirportSize = $data['destinationAirportSize'] : $destinationAirportSize = ['small_airport', 'medium_airport', 'large_airport'];
        isset($data['elevationMin']) ? $elevationMin = $data['elevationMin'] : $elevationMin = -2000;
        isset($data['elevationMax']) ? $elevationMax = $data['elevationMax'] : $elevationMax = 18000;
        isset($data['rwyLengthMin']) ? $rwyLengthMin = $data['rwyLengthMin'] : $rwyLengthMin = 0;
        isset($data['rwyLengthMax']) ? $rwyLengthMax = $data['rwyLengthMax'] : $rwyLengthMax = 17000;
        
        isset($data['arrivalWhitelist']) ? $arrivalWhitelist = $data['arrivalWhitelist'] : $arrivalWhitelist = null;
        isset($data['limit']) ? $resultLimit = $data['limit'] : $resultLimit = 10;

        /**
        *
        *  Fetch the requested data
        *
        */

        $departure = Airport::where('icao', $data['departure'])->get()->first();

        $airports = collect();
        $airports = Airport::findWithCriteria($continent, $departure->iso_country, $departure->icao, $destinationAirportSize, $arrivalWhitelist, $filterByScores, $destinationRunwayLights, $destinationAirbases);

        $suggestedAirports = $airports->filterWithCriteria($departure, $codeletter, $airtimeMin, $airtimeMax, $metcon, $rwyLengthMin, $rwyLengthMax, $elevationMin, $elevationMax);
        $suggestedAirports = $suggestedAirports->shuffle(); 

        $suggestedAirports = $suggestedAirports->sortByScores($filterByScores);
        $suggestedAirports = $suggestedAirports->splice(0,$resultLimit);

        /**
        *
        *  Prepare the data for the response
        *
        */
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
                "metar" => (config('app.env') == 'production') ? $airport->metar->metar : 'TEST-DATA '.$airport->metar->metar,
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
            "metar" => (config('app.env') == 'production') ? $departure->metar->metar : 'TEST-DATA '.$departure->metar->metar,
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