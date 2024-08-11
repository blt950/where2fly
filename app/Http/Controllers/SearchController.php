<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Airport;
use App\Models\Airline;
use App\Models\Flight;
use App\Models\Aircraft;
use App\Http\Controllers\ScoreController;
use App\Helpers\CalculationHelper;
use App\Rules\AirportExists;
use App\Rules\FlightDirection;

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
        $prefilledIcao = request()->input('icao');
        return view('front.arrivals', compact('airlines', 'aircrafts', 'prefilledIcao'));
    }

    /**
     * Display a listing of the resource.
     * 
     */
    public function indexDepartureSearch(){
        $airlines = Airline::where('has_flights', true)->orderBy('name')->get();
        $aircrafts = Aircraft::all()->pluck('icao')->sort();
        $prefilledIcao = request()->input('icao');
        return view('front.departures', compact('airlines', 'aircrafts', 'prefilledIcao'));
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
            'flightDirection' => ['required', new FlightDirection],
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
        ($data['flightDirection'] != 0) ? $flightDirection = $data['flightDirection'] : $flightDirection = null;

        (isset($data['destinationAirportSize']) && !empty($data['destinationAirportSize'])) ? $destinationAirportSize = $data['destinationAirportSize'] : $destinationAirportSize = ['small_airport', 'medium_airport', 'large_airport'];
        
        $elevationMin = (int)$data['elevationMin'];
        $elevationMax = (int)$data['elevationMax'];
        $rwyLengthMin = (int)$data['rwyLengthMin'];
        $rwyLengthMax = (int)$data['rwyLengthMax'];

        isset($data['airlines']) ? $filterByAirlines = $data['airlines'] : $filterByAirlines = null;
        isset($data['aircrafts']) ? $filterByAircrafts = $data['aircrafts'] : $filterByAircrafts = null;

        [$minDistance, $maxDistance] = CalculationHelper::aircraftNmPerHourRange($codeletter, $airtimeMin, $airtimeMax);

        /**
        *
        *  Fetch the requested data
        *
        */

        // Lets find an result with the given criteria. Give it a few attempts before we give up.
        $maxAttempts = 10;
        for($attempt = 1; $attempt <= $maxAttempts; $attempt++){
    
            // Use the supplied departure or select a random airport
            $suggestedAirport = false;
            if(isset($data['icao'])){
                $primaryAirport = Airport::where('icao', $data['icao'])->orWhere('local_code', $data['icao'])->get()->first();
            } else {
                // Select primary airport based on the criteria
                $primaryAirport = Airport::airportOpen()->isAirportSize($destinationAirportSize)->inContinent($continent)
                ->filterRunwayLengths($rwyLengthMin, $rwyLengthMax, $codeletter)->filterRunwayLights($destinationRunwayLights)
                ->filterAirbases($destinationAirbases)->filterByScores($filterByScores)->filterRoutesAndAirlines(null, $filterByAirlines, $filterByAircrafts, $destinationWithRoutesOnly)
                ->has('metar')->with('runways', 'scores', 'metar')->get();
            
                if(!$primaryAirport || !$primaryAirport->count()){
                    return back()->withErrors(['airportNotFound' => 'No suitable airport combination could be found with given criteria'])->withInput();
                }
            
                $primaryAirport = $primaryAirport->sortByScores($filterByScores)->shuffle()->slice(0, 10)->random();
                $suggestedAirport = true;
            }

            // Get airports according to filter
            $airports = collect();
            $airports = Airport::airportOpen()->notIcao($primaryAirport->icao)->isAirportSize($destinationAirportSize)
            ->inContinent($continent, $primaryAirport->iso_country)->withinDistance($primaryAirport, $minDistance, $maxDistance, $primaryAirport->icao)->withinBearing($primaryAirport, $flightDirection, $minDistance, $maxDistance)
            ->filterRunwayLengths($rwyLengthMin, $rwyLengthMax, $codeletter)->filterRunwayLights($destinationRunwayLights)
            ->filterAirbases($destinationAirbases)->filterByScores($filterByScores)->filterRoutesAndAirlines($primaryAirport->icao, $filterByAirlines, $filterByAircrafts, $destinationWithRoutesOnly)
            ->has('metar')->with('runways', 'scores', 'metar')->get();


            // Filter the eligible airports
            $suggestedAirports = $airports->filterWithCriteria($primaryAirport, $codeletter, $airtimeMin, $airtimeMax, $metcon, $rwyLengthMin, $rwyLengthMax, $elevationMin, $elevationMax);

            // Shuffle the results before sort as slim results will quickly show airports from close by location
            // Sort the suggested airports based on the intended filters
            $suggestedAirports = $suggestedAirports->shuffle(); 
            $suggestedAirports = $suggestedAirports->sortByScores($sortByScores);
            $suggestedAirports = $suggestedAirports->splice(0,20);
            $suggestedAirports = $suggestedAirports->addFlights($primaryAirport, $direction);

            // If max distance is over 1600 and bearing is enabled -> give user warning about inaccuracy
            $bearingWarning = false;
            if($maxDistance > 2300 && isset($flightDirection)){
                $bearingWarning = "Use the destination region filter instead of flight direction for longer hauls, this avoids false positives, skewed or no results.";
            }

            if($suggestedAirports->count()){
                return view('search.airports', compact('suggestedAirports', 'primaryAirport', 'direction', 'suggestedAirport', 'filterByScores', 'sortByScores', 'filterByAircrafts', 'bearingWarning'));
            }

        }

        if($direction == 'departure'){
            return redirect(route('front'))->withErrors(['airportNotFound' => 'No suitable arrival airport could be found with given criteria', 'bearingWarning' => $bearingWarning])->withInput();
        } else {
            return redirect(route('front'))->withErrors(['airportNotFound' => 'No suitable arrival airport could be found with given criteria', 'bearingWarning' => $bearingWarning])->withInput();
        }
    }

    /**
     * Search for a route
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function searchRoutes(Request $request){

        $data = request()->validate([
            'departure' => ['required', new AirportExists],
            'arrival' => ['required', new AirportExists],
            'sort' => 'required|in:flight,airline,timestamp',
        ]);

        $departure = Airport::where('icao', $data['departure'])->orWhere('local_code', $data['departure'])->get()->first();
        $arrival = Airport::where('icao', $data['arrival'])->orWhere('local_code', $data['arrival'])->get()->first();

        $routes = Flight::where('airport_dep_id', $departure->id)->where('airport_arr_id', $arrival->id)->whereHas('airline')->with('airline', 'aircrafts')->get();

        if($routes->count() == 0){
            return back()->withErrors(['routeNotFound' => 'No routes found between '.$departure->icao.' and '.$arrival->icao]);
        }
        
        // Strip the stars from IATA codes for the logos to display correctly
        $routes = $routes->map(function($route){
            $route->airline->iata_code = str_replace('*', '', $route->airline->iata_code);
            return $route;
        });

        // Sort the routes based on the selected criteria
        switch($data['sort']){
            case 'flight':
                $routes = $routes->sortBy('flight_icao');
                break;
            case 'timestamp':
                $routes = $routes->sortByDesc('last_seen_at');
                break;
        }

        if($routes->count()){
            return view('search.routes', compact('routes', 'departure', 'arrival'));
        } else {
            return back()->withErrors(['routeNotFound' => 'No routes found between '.$departure->icao.' and '.$arrival->icao]);
        }

    }
}
