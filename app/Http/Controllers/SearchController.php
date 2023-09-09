<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Models\Airport;
use App\Models\Airline;
use App\Models\Flight;
use App\Models\AirportScore;
use App\Http\Controllers\ScoreController;

class SearchController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
    */
    public function index(){
        $airlines = \App\Models\Airline::orderBy('name')->has('flights')->get();
        return view('front', compact('airlines'));
    }

    /**
     * Search for a flight
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function search(Request $request){

        /**
        *
        *  Validate the request and mapping of arguments
        *
        */

        $data = request()->validate([
            'departure' => 'nullable|exists:App\Models\Airport,icao',
            'continent' => 'required|string',
            'codeletter' => 'required|string',
            'airtimeMin' => 'required|numeric|between:0,12',
            'airtimeMax' => 'required|numeric|between:0,12',
            'sortByWeather' => 'in:0,1',
            'sortByATC' => 'in:0,1',
            'scores' => 'sometimes|array',
            'metcondition' => 'required|in:IFR,VFR,ANY',
            'destinationWithRoutesOnly' => 'required|numeric|between:-1,1',
            'destinationRunwayLights' => 'required|numeric|between:-1,1',
            'destinationAirbases' => 'required|numeric|between:-1,1',
            'destinationAirportSize' => 'sometimes|array',
            'elevationMin' => 'required|numeric|between:-2000,18000',
            'elevationMax' => 'required|numeric|between:-2000,18000',
            'rwyLengthMin' => 'required|numeric|between:0,17000',
            'rwyLengthMax' => 'required|numeric|between:0,17000',
            'airlines' => 'sometimes|array',
        ]);
        
        $continent = $data['continent'];
        $codeletter = $data['codeletter'];
        $airtimeMin = (int)$data['airtimeMin'];
        $airtimeMax = (int)$data['airtimeMax'];
        if($airtimeMax == 12) $airtimeMax = 24; // If airtime is 12+ hours, bump it

        // Create a filter array based on input
        $sortByScores = [];
        isset($data['sortByWeather']) ? $sortByScores = array_merge($sortByScores, ScoreController::getWeatherTypes()) : null;
        isset($data['sortByATC']) ? $sortByScores = array_merge($sortByScores, ScoreController::getVatsimTypes()) : null;

        $filterByScores = array_map('intval', $data['scores']);

        $metcon = $data['metcondition'];
        $destinationWithRoutesOnly = (int)$data['destinationWithRoutesOnly'];
        $destinationRunwayLights = (int)$data['destinationRunwayLights'];
        $destinationAirbases = (int)$data['destinationAirbases'];

        (isset($data['destinationAirportSize']) && !empty($data['destinationAirportSize'])) ? $destinationAirportSize = $data['destinationAirportSize'] : $destinationAirportSize = ['small_airport', 'medium_airport', 'large_airport'];
        
        $elevationMin = (int)$data['elevationMin'];
        $elevationMax = (int)$data['elevationMax'];
        $rwyLengthMin = (int)$data['rwyLengthMin'];
        $rwyLengthMax = (int)$data['rwyLengthMax'];

        isset($data['airlines']) ? $filterByAirlines = $data['airlines'] : $filterByAirlines = null;

        /**
        *
        *  Fetch the requested data
        *
        */

        // Use the supplied departure or select a random from toplist
        $suggestedDeparture = false;
        if(isset($data['departure'])){
            $departure = Airport::where('icao', $data['departure'])->get()->first();
        } else {
            // Get a random airport from the toplist
            $departure = Airport::findWithCriteria($continent, null, null, null, null, null, null, null, $destinationWithRoutesOnly, $filterByAirlines, 'departureFlights')->sortByScores($filterByScores)->shuffle()->slice(0, 10)->random();
            $suggestedDeparture = true;
        }

        // Get airports according to filter
        $airports = collect();
        $airports = Airport::findWithCriteria($continent, $departure->iso_country, $departure->icao, $destinationAirportSize, null, $filterByScores, $destinationRunwayLights, $destinationAirbases, $destinationWithRoutesOnly, $filterByAirlines);

        // Filter the eligable airports
        $suggestedAirports = $airports->filterWithCriteria($departure, $codeletter, $airtimeMin, $airtimeMax, $metcon, $rwyLengthMin, $rwyLengthMax, $elevationMin, $elevationMax);

        // Shuffle the results before sort as slim results will quickly show airports from close by location
        // Sort the suggested airports based on the intended filters
        $suggestedAirports = $suggestedAirports->shuffle(); 
        $suggestedAirports = $suggestedAirports->sortByScores($sortByScores);
        $suggestedAirports = $suggestedAirports->splice(0,20);
        $suggestedAirports = $suggestedAirports->addFlights($departure);

        return view('search', compact('suggestedAirports', 'departure', 'suggestedDeparture', 'filterByScores', 'sortByScores'));
    }
}
