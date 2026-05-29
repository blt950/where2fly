<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\AirportResource;
use App\Models\AirportScore;
use Illuminate\Http\Request;

class TopController extends Controller
{
    public function index(Request $request)
    {

        $data = $request->validate([
            'continent' => 'sometimes|in:["AF","AS","EU","NA","OC","SA"]',
            'limit' => 'sometimes|integer|between:1,30',
        ]);
        $continent = $data['continent'] ?? null;
        $resultLimit = $data['limit'] ?? 10;

        $airportScores = AirportScore::getTopAirports($continent, null, $resultLimit);
        $airports = AirportResource::collection($airportScores->pluck('airport'));

        return response()->json([
            'message' => 'Success',
            'data' => $airports,
        ], 200);

    }

    public function indexWhitelist(Request $request)
    {

        $data = $request->validate([
            'whitelist' => 'required|array',
            'limit' => 'sometimes|integer|between:1,30',
        ]);

        $resultLimit = $data['limit'] ?? 10;

        $airportScores = AirportScore::getTopAirports(null, $data['whitelist'], $resultLimit);
        $airports = AirportResource::collection($airportScores->pluck('airport'));

        return response()->json([
            'message' => 'Success',
            'data' => $airports,
        ], 200);

    }
}
