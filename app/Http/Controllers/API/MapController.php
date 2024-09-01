<?php

namespace App\Http\Controllers\API;

use App\Models\Airport;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\UserList;
use App\Helpers\MapHelper;
use Illuminate\Support\Facades\Auth;
use App\Rules\AirportExists;
use App\Models\Flight;
use App\Models\Airline;

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
    public function getAirport(Request $request)
    {
        $data = request()->validate([
            'primaryAirport' => ['nullable', 'exists:airports,id'],
            'secondaryAirport' => ['required', 'exists:airports,id'],
            'reverseDirection' => ['nullable'],
        ]);   

        $primaryAirport = $data['primaryAirport'];
        $secondaryAirport = $data['secondaryAirport'];
        $direction = $data['reverseDirection'];

        // If direction is set, let's search for airlines
        $airlines = null;
        if(isset($direction)){
            $flights = null;
            $arrivalAirportColumn = $direction == true ? 'airport_dep_id' : 'airport_arr_id';
            $departureAirportColumn = $direction == true ? 'airport_arr_id' : 'airport_dep_id';

            // Get flights and airlines for the suggested airports
            $flights = Flight::select('airline_icao')->where('seen_counter', '>', 3)->where($arrivalAirportColumn, $secondaryAirport)->where($departureAirportColumn, $primaryAirport)->get();
            $airlines = Airline::whereIn('icao_code', $flights->pluck('airline_icao')->unique())->get();

            // Replace * with '' in all airline iata codes
            foreach ($airlines as $airline) {
                $airline->iata_code = str_replace('*', '', $airline->iata_code);
            }
        }

        $airport = Airport::select('id', 'icao', 'name', 'iso_country')->with(['runways' => function ($query) {
            $query->where('closed', false)->whereNotNull('length_ft');
        }])->where('id', $secondaryAirport)->first();
        $metar = $airport->metar;

        if(isset($airport)) {
            return response()->json(['message' => 'Success', 'data' => [
                'airport' => $airport->toArray(),
                'metar' => $metar->metar,
                'airlines' => $airlines,
            ]], 200);
        } else {
            return response()->json(['message' => 'Airport not found'], 404);
        }
    }



}
