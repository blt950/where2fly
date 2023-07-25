<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AirportScore;

class TopController extends Controller{
    
    public function index(Request $request){

        $data = $request->validate([
            'continent' => 'sometimes|in:["AF","AS","EU","NA","OC","SA"]',
        ]);
        $continent = $data['continent'] ?? null;

        $airportScores = AirportScore::getTopAirports($continent);
        $airports = $this->prepareResponse($airportScores);

        return response()->json([
            'message' => 'Success',
            'data' => $airports
        ], 200);
    
    }

    public function indexWhitelist(Request $request){

        $data = $request->validate([
            'whitelist' => 'required|array',
        ]);

        $airportScores = AirportScore::getTopAirports(null, $data['whitelist']);
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
                "metar" => $as->airport->metar->metar,
                "longestRwyFt" => $as->airport->longestRunway(),
                "scores" => $scores,
            
            ]);
        }

        return $result;
    }

}