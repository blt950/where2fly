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
        return view('front');
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

        // Fetch the requested score filtering and cast it to numbers
        $filterByScores = collect($data['scores'])->map(function($item, $key){
            return (int)$item;
        });

        $metcon = $data['metcondition'];
        $destinationWithRoutesOnly = (int)$data['destinationWithRoutesOnly'];
        $destinationRunwayLights = (int)$data['destinationRunwayLights'];
        $destinationAirbases = (int)$data['destinationAirbases'];

        $destinationAirportSize = $data['destinationAirportSize'];
        if(!$destinationAirportSize || empty($destinationAirportSize)){
            $destinationAirportSize = null;
        }
        
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
            $departure = Airport::findWithCriteria($continent)->sortByFilteredScores($filterByScores)->slice(0, 10)->random();
            $suggestedDeparture = true;
        }

        // Get airports according to filter
        $airports = collect();
        $airports = Airport::findWithCriteria($continent, $departure->iso_country, $departure->icao, $filterByScores, $destinationWithRoutesOnly, $destinationRunwayLights, $destinationAirbases, $destinationAirportSize, $filterByAirlines);

        $suggestedAirports = $airports; // TEMP

        // Filter the eligable airports
        //TODO below: Filter with the new variables as well.
        //$suggestedAirports = $airports->filterWithCriteria($departure, $codeletter, $airtimeMin, $airtimeMax, $metcon, $filteredScores, $rwyLengthMin, $rwyLengthMax, $elevationMin, $elevationMax);

        // Shuffle the results before sort as slim results will quickly show airports from close by location
        // Sort the suggested airports based on the intended filters
        $suggestedAirports = $suggestedAirports->shuffle(); 
        // @TODO: Perhaps fix this function to be called SortBy scores or something.
        //$suggestedAirports = $suggestedAirports->sortByFilteredScores($filteredScores);
        $suggestedAirports = $suggestedAirports->splice(0,20);
        $suggestedAirports = $suggestedAirports->addFlights($departure);

        return view('search', compact('suggestedAirports', 'departure', 'suggestedDeparture'));
    }

    /**
     * Advanced search for a flight
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function searchAdvanced(Request $request){
    
        $data = request()->validate([
            'departure' => 'nullable|exists:App\Models\Airport,icao',
            'continent' => 'required|string',
            'codeletter' => 'required|string',
            'rwyLengthMin' => 'required|between:0,16000',
            'rwyLengthMax' => 'required|between:0,16000',
            'airtimeMin' => 'required|between:0,24',
            'airtimeMax' => 'required|between:0,24',
            'elevationMin' => 'required|between:-2000,18000',
            'elevationMax' => 'required|between:-2000,18000',
            'scores' => 'sometimes|array',
            'metcondition' => 'required|in:IFR,VFR,ANY',
            'airportExclusions' => 'sometimes|array',
            'airportWithRoutesOnly' => 'sometimes|array'
        ]);

        $continent = $data['continent'];
        $codeletter = $data['codeletter'];
        $rwyLengthMin = (int)$data['rwyLengthMin'];
        $rwyLengthMax = (int)$data['rwyLengthMax'];
        $airtimeMin = (int)$data['airtimeMin'];
        $airtimeMax = (int)$data['airtimeMax'];
        $elevationMin = (int)$data['elevationMin'];
        $elevationMax = (int)$data['elevationMax'];
        isset($data['scores']) ? $filteredScores = $data['scores'] : $filteredScores = null;
        isset($data['airportExclusions']) ? $airportExclusions = $data['airportExclusions'] : $airportExclusions = null;
        isset($data['airportWithRoutesOnly']) ? $airportWithRoutesOnly = true : $airportWithRoutesOnly = false;
        $metcon = $data['metcondition'];
        
        // Use the supplied departure or select a random from toplist
        $suggestedDeparture = false;
        if(isset($data['departure'])){
            $departure = Airport::where('icao', $data['departure'])->get()->first();
        } else {
            // Get a random airport from the toplist
            $departure = Airport::findWithCriteria($continent, null, null, null, $airportExclusions)->sortByFilteredScores($filteredScores)->slice(0, 10)->random();
            $suggestedDeparture = true;
        }

        // Get airports according to filter
        $airports = collect();
        $airports = Airport::findWithCriteria($continent, $departure->iso_country, $departure->icao, null, $airportExclusions, $airportWithRoutesOnly);

        // Filter the eligable airports
        $suggestedAirports = $airports->filterWithCriteria($departure, $codeletter, $airtimeMin, $airtimeMax, $metcon, $filteredScores, $rwyLengthMin, $rwyLengthMax, $elevationMin, $elevationMax);

        // Shuffle the results before sort as slim results will quickly show airports from close by location
        // Sort the suggested airports based on the intended filters
        $suggestedAirports = $suggestedAirports->shuffle(); 
        $suggestedAirports = $suggestedAirports->sortByFilteredScores($filteredScores);
        $suggestedAirports = $suggestedAirports->splice(0,20);
        $suggestedAirports = $suggestedAirports->addFlights($departure);

        // Set the advanced search flag
        $wasAdvancedSearch = true;

        // Return the view
        return view('search', compact('suggestedAirports', 'filteredScores', 'departure', 'suggestedDeparture', 'wasAdvancedSearch'));
    }
}
