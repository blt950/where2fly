<?php

namespace App\Http\Controllers\API;

use App\Models\Airport;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\UserList;
use App\Helpers\MapHelper;
use Illuminate\Support\Facades\Auth;

class MapController extends Controller
{

    /**
     * Get airports from lists
     */
    public function getListAirports(){
        $userLists = UserList::where('user_id', Auth::id())->with('airports')->get();

        $airportsMapCollection = MapHelper::getAirportsFromUserLists($userLists);
        $airportMapData = MapHelper::generateAirportMapDataFromAirports($airportsMapCollection);

        return response()->json(['message' => 'Success', 'data' => $airportMapData], 200);
    }

    /**
     * Get the airport list for airport card
     */
    public function getAirport(int $airportId)
    {
        $airport = Airport::select('id', 'icao', 'name', 'iso_country')->with(['runways' => function ($query) {
            $query->where('closed', false)->whereNotNull('length_ft');
        }])->where('id', $airportId)->first();
        $metar = $airport->metar;

        if(isset($airport)) {
            return response()->json(['message' => 'Success', 'data' => [
                'airport' => $airport->toArray(),
                'metar' => $metar->metar
            ]], 200);
        } else {
            return response()->json(['message' => 'Airport not found'], 404);
        }
    }



}
