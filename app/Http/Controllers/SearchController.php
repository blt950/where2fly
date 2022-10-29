<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Models\Airport;

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
        $data = request()->validate([
            'departure' => 'required|exists:App\Models\Airport,icao',
            'codeletter' => 'required|string',
            'continent' => 'required|string',
            'airtimeMin' => 'required|between:0,8',
            'airtimeMax' => 'required|between:0,8',
            'filterWeather' => 'in:0,1',
            'filterATC' => 'in:0,1',
        ]);
        
        $departure = Airport::where('icao', $data['departure'])->get()->first();
        $codeletter = $data['codeletter'];
        $continent = $data['continent'];
        $airtimeMin = (int)$data['airtimeMin'];
        $airtimeMax = (int)$data['airtimeMax'];
        if($airtimeMax == 8) $airtimeMax = 24; // If airtime is 8+ hours, bump it
        isset($data['filterWeather']) ? $filterWeather = (boolean)$data['filterWeather'] : $filterWeather = false;
        isset($data['filterATC']) ? $filterATC = (boolean)$data['filterATC'] : $filterATC = false;

        // Get airports according to filter
        $airports = collect();
        if($continent == "DO"){
            // Domestic airports only
            $airports = Airport::where('type', '!=', 'closed')->where('iso_country', $departure->iso_country)->where('icao', '!=', $departure->icao)->has('metar')->with('runways', 'scores', 'metar')->get();
        } else {
            $airports = Airport::where('type', '!=', 'closed')->where('continent', $continent)->where('icao', '!=', $departure->icao)->has('metar')->with('runways', 'scores', 'metar')->get();
        }
    
        // Check eligable airports
        $suggestedAirports = collect();
        $distances = [];
        $airtimes = [];
        foreach($airports as $destination){
            // Is it within the intended airtime?
            $aircraftNmPerHour = $this::aircraftNmPerHour($codeletter);
            $distance = $this::distance($departure->latitude_deg, $departure->longitude_deg, $destination->latitude_deg, $destination->longitude_deg, "N");

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
            $suggestedAirports = $suggestedAirports->sortByDesc('total_score');
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

        return view('search', compact('suggestedAirports', 'distances', 'airtimes'));
    }

    public static function aircraftNmPerHour(string $actCode){

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

    public static function timeClimbDescend(string $actCode){
        
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


    public static function distance($lat1, $lon1, $lat2, $lon2, $unit) {

        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $unit = strtoupper($unit);
      
        if ($unit == "K") {
            return ($miles * 1.609344);
        } else if ($unit == "N") {
            return ($miles * 0.8684);
        } else {
            return $miles;
        }
    }
}
