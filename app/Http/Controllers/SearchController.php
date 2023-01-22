<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Models\Airport;
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
            'departure' => 'required|exists:App\Models\Airport,icao',
            'continent' => 'required|string',
            'codeletter' => 'required|string',
            'airtimeMin' => 'required|between:0,8',
            'airtimeMax' => 'required|between:0,8',
            'filterWeather' => 'in:0,1',
            'filterATC' => 'in:0,1',
        ]);
        
        $departure = Airport::where('icao', $data['departure'])->get()->first();
        $continent = $data['continent'];
        $codeletter = $data['codeletter'];
        $airtimeMin = (int)$data['airtimeMin'];
        $airtimeMax = (int)$data['airtimeMax'];
        if($airtimeMax == 8) $airtimeMax = 24; // If airtime is 8+ hours, bump it
        isset($data['filterWeather']) ? $filterWeather = (boolean)$data['filterWeather'] : $filterWeather = false;
        isset($data['filterATC']) ? $filterATC = (boolean)$data['filterATC'] : $filterATC = false;

        // Get airports according to filter
        $airports = collect();
        if($continent == "DO"){
            // Domestic airports only
            $airports = Airport::where('type', '!=', 'closed')
                        ->whereIn('type', ['large_airport','medium_airport','seaplane_base','small_airport'])
                        ->where('iso_country', $departure->iso_country)
                        ->where('icao', '!=', $departure->icao)
                        ->has('metar')
                        ->with('runways', 'scores', 'metar')
                        ->get();
        } else {
            $airports = Airport::where('type', '!=', 'closed')
                        ->whereIn('type', ['large_airport','medium_airport','seaplane_base','small_airport'])
                        ->where('continent', $continent)
                        ->where('icao', '!=', $departure->icao)
                        ->has('metar')
                        ->with('runways', 'scores', 'metar')
                        ->get();
        }
    
        // Check eligable airports
        $suggestedAirports = collect();
        $distances = [];
        $airtimes = [];
        foreach($airports as $destination){
            // Is it within the intended airtime?
            $aircraftNmPerHour = $this::aircraftNmPerHour($codeletter);
            $distance = distance($departure->latitude_deg, $departure->longitude_deg, $destination->latitude_deg, $destination->longitude_deg, "N");

            $estimatedAirtime = ($distance / $aircraftNmPerHour) + $this::timeClimbDescend($codeletter);

            if($estimatedAirtime >= $airtimeMin && $estimatedAirtime <= $airtimeMax){
                if($destination->supportsAircraftCode($codeletter)){
                    $suggestedAirports->push($destination);
                    $distances[$destination->icao] = round($distance);
                    $airtimes[$destination->icao] = round($estimatedAirtime, 1);
                }
            }
        }

        // Some ranking filters are applied, we need to run custom ranking
        if($filterWeather && $filterATC){
            
            $suggestedAirports = $suggestedAirports->sort(function ($a, $b) {
                if($a->scores->count() == $b->scores->count()) return 0;
                return ($a->scores->count() > $b->scores->count()) ? -1 : 1;
            });
        } elseif($filterWeather && !$filterATC){

            $suggestedAirports = $suggestedAirports->sort(function ($a, $b) {
                if($a->weatherScore() == $b->weatherScore()) return 0;
                return ($a->weatherScore() > $b->weatherScore()) ? -1 : 1;
            });
        } elseif(!$filterWeather && $filterATC){

            $suggestedAirports = $suggestedAirports->sort(function ($a, $b) {
                if($a->vatsimScore() == $b->vatsimScore()) return 0;
                return ($a->vatsimScore() > $b->vatsimScore()) ? -1 : 1;
            });
        } else {

            // Let's randomize so it doesn't display database order
            $suggestedAirports = $suggestedAirports->shuffle();

        }

        $suggestedAirports = $suggestedAirports->slice(0, 10);

        return view('search', compact('suggestedAirports', 'distances', 'airtimes', 'departure'));
    }

    /**
     * Advanced search for a flight
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function searchAdvanced(Request $request){

        $data = request()->validate([
            'departure' => 'required|exists:App\Models\Airport,icao',
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

        $departure = Airport::where('icao', $data['departure'])->get()->first();
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

        // Get airports according to filter
        $airports = collect();
        if($continent == "DO"){
            // Domestic airports only
            $airports = Airport::where('type', '!=', 'closed')->where('type', ['large_airport','medium_airport','seaplane_base','small_airport'])->where('iso_country', $departure->iso_country)->where('icao', '!=', $departure->icao)->has('metar')->with('runways', 'scores', 'metar')->get();
        } else {
            $airports = Airport::where('type', '!=', 'closed')->where('type', ['large_airport','medium_airport','seaplane_base','small_airport'])->where('continent', $continent)->where('icao', '!=', $departure->icao)->has('metar')->with('runways', 'scores', 'metar')->get();
        }

        // Check eligable airports
        $suggestedAirports = collect();
        $distances = [];
        $airtimes = [];
        $scoreboard = [];
        foreach($airports as $destination){

            // Check destination METAR and filtered Metcon
            if($metcon == "VFR" && !$destination->hasVisualCondition()){
                continue;
            } elseif($metcon == "IFR" && $destination->hasVisualCondition()){
                continue;
            }

            // If score filter is enabled, disqualify airports who does not meet the requirement
            if($filteredScores && $destination->scores->whereIn('reason', $filteredScores)->count() == 0){
                continue;
            }

            // Is it within the intended airtime?
            $aircraftNmPerHour = $this::aircraftNmPerHour($codeletter);
            $distance = distance($departure->latitude_deg, $departure->longitude_deg, $destination->latitude_deg, $destination->longitude_deg, "N");

            $estimatedAirtime = ($distance / $aircraftNmPerHour) + $this::timeClimbDescend($codeletter);

            if($estimatedAirtime >= $airtimeMin && $estimatedAirtime <= $airtimeMax){
                if($destination->longestRunway() >= $rwyLengthMin && $destination->longestRunway() <= $rwyLengthMax){
                    if($destination->elevation_ft >= $elevationMin && $destination->elevation_ft <= $elevationMax){
                        $suggestedAirports->push($destination);
                        $distances[$destination->icao] = round($distance);
                        $airtimes[$destination->icao] = round($estimatedAirtime, 1);
                    }
                }
            }

        }

        // Sort the suggested airports based on the intended filters
        $suggestedAirports = $suggestedAirports->shuffle(); // Shuffle the results before sort as slim results will quickly show airports from close by location
        $suggestedAirports = $suggestedAirports->sort(function($a, $b) use ($filteredScores){

            $aScore = $a->scores->whereIn('reason', $filteredScores)->count();
            $bScore = $b->scores->whereIn('reason', $filteredScores)->count();

            if($aScore == $bScore) return 0;
            return ($aScore > $bScore) ? -1 : 1;

        });

        $suggestedAirports = $suggestedAirports->splice(0,10);

        return view('search', compact('suggestedAirports', 'distances', 'airtimes', 'filteredScores', 'departure'));
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
