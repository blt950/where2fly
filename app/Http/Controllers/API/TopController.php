<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AirportScore;

class TopController extends Controller{
    
    public function index(Request $request){

        $data = $request->validate([
            'continent' => 'sometimes|in:["AF","AS","EU","NA","OC","SA"]',
            'limit' => 'sometimes|integer|between:1,30',
        ]);
        $continent = $data['continent'] ?? null;
        isset($data['limit']) ? $resultLimit = $data['limit'] : $resultLimit = 10;

        $airportScores = AirportScore::getTopAirports($continent, null, $resultLimit);
        $airports = $this->prepareResponse($airportScores);

        return response()->json([
            'message' => 'Success',
            'data' => $airports
        ], 200);
    
    }

    public function indexWhitelist(Request $request){

        $data = $request->validate([
            'whitelist' => 'required|array',
            'limit' => 'sometimes|integer|between:1,30',
        ]);

        isset($data['limit']) ? $resultLimit = $data['limit'] : $resultLimit = 10;

        $airportScores = AirportScore::getTopAirports(null, $data['whitelist'], $resultLimit);
        $airports = $this->prepareResponse($airportScores);
        

        return response()->json([
            'message' => 'Success',
            'data' => $airports
        ], 200);

    }

    private function prepareResponse($airportScores){
        $result = collect();

        foreach($airportScores as $as){

            $scores = $as->airport->scores->pluck('reason');

            $result->push([

                "name" => $as->airport->name,
                "icao" => $as->airport->icao,
                "iata" => $as->airport->iata_code ? $as->airport->iata_code : null,
                "contient" => $as->airport->continent,
                "country" => $as->airport->iso_country,
                "region" => $as->airport->iso_region,
                "metar" => (config('app.env') == 'production') ? $as->airport->metar->metar : 'TEST-DATA '.$as->airport->metar->metar,
                "longestRwyFt" => $as->airport->longestRunway(),
                "scores" => $scores,
            
            ]);
        }

        return $result;
    }

}