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
use App\Models\Scenery;
use App\Models\Simulator;

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
            'highlightedAircrafts' => ['nullable', 'array'],
        ]);   

        $primaryAirport = $data['primaryAirport'];
        $secondaryAirport = $data['secondaryAirport'];
        $direction = $data['reverseDirection'];
        $highlightedAircrafts = isset($data['highlightedAircrafts']) ? collect($data['highlightedAircrafts']) : null;

        // If direction is set, let's search for airlines
        $airlines = null;
        if(isset($direction)){
            $flights = null;
            $arrivalAirportColumn = $direction == true ? 'airport_dep_id' : 'airport_arr_id';
            $departureAirportColumn = $direction == true ? 'airport_arr_id' : 'airport_dep_id';

            // Get flights and airlines for the suggested airports
            $flights = Flight::select('id', 'airline_icao')->where('seen_counter', '>', 3)->where($arrivalAirportColumn, $secondaryAirport)->where($departureAirportColumn, $primaryAirport)->with('aircrafts')->get();
            $airlines = Airline::whereIn('icao_code', $flights->pluck('airline_icao')->unique())->get();

            // Highlight airlines that have the aircrafts in the list
            if(isset($highlightedAircrafts)){
                foreach($flights as $flight){
                    foreach($flight->aircrafts as $aircraft){
                        if($highlightedAircrafts->contains($aircraft->icao)){
                            $airlines->where('icao_code', $flight->airline_icao)->first()->highlighted = true;
                        }
                    }
                }
            }

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

    /**
     * Get flights for the airport pair and given airline
     */
    public function getFlights(Request $request){
        $data = request()->validate([
            'departureAirportId' => ['required', 'exists:airports,id'],
            'arrivalAirportId' => ['required', 'exists:airports,id'],
            'airlineId' => ['required', 'exists:airlines,icao_code'],
            'highlightedAircrafts' => ['nullable', 'array'],
        ]);

        $departureAirportId = $data['departureAirportId'];
        $arrivalAirportId = $data['arrivalAirportId'];
        $airlineIcao = $data['airlineId'];
        $highlightedAircrafts = isset($data['highlightedAircrafts']) ? collect($data['highlightedAircrafts']) : null;

        $airline = Airline::where('icao_code', $airlineIcao)->first();
        $flights = Flight::where('seen_counter', '>', 3)->where('airport_dep_id', $departureAirportId)->where('airport_arr_id', $arrivalAirportId)->where('airline_icao', $airlineIcao)->with('aircrafts')->orderByDesc('last_seen_at')->get();

        // Highlight airlines that have the aircrafts in the list
        if(isset($highlightedAircrafts)){
            foreach($flights as $flight){
                foreach($flight->aircrafts as $aircraft){
                    if($highlightedAircrafts->contains($aircraft->icao)){
                        $flight->highlighted = true;
                    }
                }
            }
        }

        $airline->iata_code = str_replace('*', '', $airline->iata_code);

        if(isset($flights)){
            return response()->json(['message' => 'Success', 'data' => [
                'flights' => $flights,
                'airline' => $airline,   
            ]], 200);
        } else {
            return response()->json(['message' => 'Flights not found'], 404);
        }
    }

    /**
     * Get scenery
     */
    public function getScenery(Request $request){
        $data = request()->validate([
            'airportIcao' => ['required', 'exists:airports,icao'],
        ]);

        $airportIcao = $data['airportIcao'];
        $sceneries = Scenery::where('icao', $airportIcao)->where('published', true)->get();
        $simulators = Simulator::all();

        if(isset($sceneries)){
            return response()->json(['message' => 'Success', 'data' => [
                'sceneries' => $sceneries,
                'simulators' => $simulators,
            ]], 200);
        } else {
            return response()->json(['message' => 'Scenery not found'], 404);
        }
    }
}
