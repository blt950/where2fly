<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Airport;
use App\Models\Airline;
use App\Models\Flight;
use App\Models\AirportScore;
use App\Models\Aircraft;
use App\Http\Controllers\ScoreController;
use App\Rules\AirportExists;

class SearchController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
    */
    public function indexArrivalSearch(){
        $airlines = Airline::where('has_flights', true)->orderBy('name')->get();
        $aircrafts = Aircraft::all()->pluck('icao')->sort();
        return view('front.index', compact('airlines', 'aircrafts'));
    }

    /**
     * Display a listing of the resource.
     * 
     */
    public function indexDepartureSearch(){
        $airlines = Airline::where('has_flights', true)->orderBy('name')->get();
        $aircrafts = Aircraft::all()->pluck('icao')->sort();
        return view('front.departures', compact('airlines', 'aircrafts'));
    }

    /**
     * Display a listing of the resource.
     * 
     */
    public function indexRouteSearch(){
        return view('front.routes');
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
            'icao' => ['nullable', new AirportExists],
            'direction' => 'required',
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
            'aircrafts' => 'sometimes|array',
        ]);
        
        $direction = $data['direction'];
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
        isset($data['aircrafts']) ? $filterByAircrafts = $data['aircrafts'] : $filterByAircrafts = null;

        /**
        *
        *  Fetch the requested data
        *
        */

        // Lets find an result with the given criteria. Give it a few attempts before we give up.
        $maxAttempts = 10;
        for($attempt = 1; $attempt <= $maxAttempts; $attempt++){
    
            // Use the supplied departure or select a random from toplist
            $suggestedAirport = false;
            if(isset($data['icao'])){
                $airport = Airport::where('icao', $data['icao'])->orWhere('local_code', $data['icao'])->get()->first();
            } else {
                // Get a random airport from the toplist
                $airport = Airport::findWithCriteria($continent, null, null, $destinationAirportSize, null, $filterByScores, $destinationRunwayLights, $destinationAirbases, $destinationWithRoutesOnly, $filterByAirlines, $filterByAircrafts, $direction.'Flights');   
            
                if(!$airport || !$airport->count()){
                    return back()->withErrors(['airportNotFound' => 'No '.$direction.' airport found with given criteria']);
                }
            
                $airport = $airport->sortByScores($filterByScores)->shuffle()->slice(0, 10)->random();
                $suggestedAirport = true;
            }

            // Get airports according to filter
            $airports = collect();
            $airports = Airport::findWithCriteria($continent, $airport->iso_country, $airport->icao, $destinationAirportSize, null, $filterByScores, $destinationRunwayLights, $destinationAirbases, $destinationWithRoutesOnly, $filterByAirlines, $filterByAircrafts);    

            // Filter the eligable airports
            $suggestedAirports = $airports->filterWithCriteria($airport, $codeletter, $airtimeMin, $airtimeMax, $metcon, $rwyLengthMin, $rwyLengthMax, $elevationMin, $elevationMax);

            // Shuffle the results before sort as slim results will quickly show airports from close by location
            // Sort the suggested airports based on the intended filters
            $suggestedAirports = $suggestedAirports->shuffle(); 
            $suggestedAirports = $suggestedAirports->sortByScores($sortByScores);
            $suggestedAirports = $suggestedAirports->splice(0,20);
            $suggestedAirports = $suggestedAirports->addFlights($airport, $direction);

            if($suggestedAirports->count()){
                return view('search', compact('suggestedAirports', 'airport', 'direction', 'suggestedAirport', 'filterByScores', 'sortByScores'));
            }

        }

        return redirect(route('front'))->withErrors(['airportNotFound' => 'No airport found with given criteria']);
    }
}
