<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Models\Airport;
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
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
    */
    public function indexAdvanced(){
        return view('advanced');
    }

    /**
     * Search for a flight
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function search(Request $request){
        $data = request()->validate([
            'departure' => 'nullable|exists:App\Models\Airport,icao',
            'continent' => 'required|string',
            'codeletter' => 'required|string',
            'airtimeMin' => 'required|between:0,8',
            'airtimeMax' => 'required|between:0,8',
            'filterWeather' => 'in:0,1',
            'filterATC' => 'in:0,1',
        ]);
        
        $continent = $data['continent'];
        $codeletter = $data['codeletter'];
        $airtimeMin = (int)$data['airtimeMin'];
        $airtimeMax = (int)$data['airtimeMax'];
        if($airtimeMax == 8) $airtimeMax = 24; // If airtime is 8+ hours, bump it

        // Create a filter array based on input
        $filterByScores = [];
        isset($data['filterWeather']) ? $filterByScores = array_merge($filterByScores, ScoreController::getWeatherTypes()) : null;
        isset($data['filterATC']) ? $filterByScores = array_merge($filterByScores, ScoreController::getVatsimTypes()) : null;

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
        $airports = Airport::findWithCriteria($continent, $departure->iso_country, $departure->icao);
    
        // Check eligable airports
        $suggestedAirports = $airports->filterWithCriteria($departure, $codeletter, $airtimeMin, $airtimeMax);

        // Some ranking filters are applied, we need to run custom ranking or shuffle so data is not shown in database order
        if(!empty($filterByScores)){
            $suggestedAirports = $suggestedAirports->sortByFilteredScores($filterByScores);
        } else {
            $suggestedAirports = $suggestedAirports->shuffle();
        }

        $suggestedAirports = $suggestedAirports->slice(0, 20);

        $wasAdvancedSearch = false;
        return view('search', compact('suggestedAirports', 'departure', 'suggestedDeparture', 'wasAdvancedSearch'));
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
            'metcondition' => 'required|in:IFR,VFR,ANY'
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
        $metcon = $data['metcondition'];

        // Use the supplied departure or select a random from toplist
        $suggestedDeparture = false;
        if(isset($data['departure'])){
            $departure = Airport::where('icao', $data['departure'])->get()->first();
        } else {
            // Get a random airport from the toplist
            $departure = Airport::findWithCriteria($continent)->sortByFilteredScores($filteredScores)->slice(0, 10)->random();
            $suggestedDeparture = true;
        }

        // Get airports according to filter
        $airports = collect();
        $airports = Airport::findWithCriteria($continent, $departure->iso_country, $departure->icao);

        // Filter the eligable airports
        $suggestedAirports = $airports->filterWithCriteria($departure, $codeletter, $airtimeMin, $airtimeMax, $metcon, $filteredScores, $rwyLengthMin, $rwyLengthMax, $elevationMin, $elevationMax);

        // Shuffle the results before sort as slim results will quickly show airports from close by location
        // Sort the suggested airports based on the intended filters
        $suggestedAirports = $suggestedAirports->shuffle(); 
        $suggestedAirports = $suggestedAirports->sortByFilteredScores($filteredScores);
        $suggestedAirports = $suggestedAirports->splice(0,20);

        $wasAdvancedSearch = true;
        return view('search', compact('suggestedAirports', 'filteredScores', 'departure', 'suggestedDeparture', 'wasAdvancedSearch'));
    }


    /**
     *  Calculate aircraft travel nautrical miles per hour
     *
     * @param  string $actCode Aircraft code
     * @return int Cruice speed
     */
    private static function aircraftNmPerHour(string $actCode){

        $crzSpeed = 0;
        switch($actCode){
            case "A":
                $crzSpeed = 115;
                break;
            case "B":
                $crzSpeed = 360;
                break;
            case "C":
                $crzSpeed = 460;
                break;
            case "D":
                $crzSpeed = 480;
                break;
            case "E":
                $crzSpeed = 510;
                break;
            case "F":
                $crzSpeed = 520;
                break;
            default:
                $crzSpeed = 0;
        }

        return $crzSpeed;
    }

    /**
     *  Calculate minute addition for climbing the aircraft
     *
     * @param  string $actCode Aircraft code
     * @return int Additional minutes
     */
    private static function timeClimbDescend(string $actCode){
        
        $addMinutes = 0;
        switch($actCode){
            case "A":
                $addMinutes = 0.35;
                break;
            case "B":
                $addMinutes = 0.35;
                break;
            case "C":
                $addMinutes = 0.5;
                break;
            case "D":
                $addMinutes = 0.5;
                break;
            case "E":
                $addMinutes = 0.5;
                break;
            case "F":
                $addMinutes = 0.5;
                break;
            default:
                $addMinutes = 0;
        }

        return $addMinutes;

    }
}
