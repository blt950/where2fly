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
            'airtime' => 'required|between:1,5',
        ]);
        
        $departure = Airport::where('icao', $data['departure'])->get()->first();
        $codeletter = $data['codeletter'];
        $continent = $data['continent'];
        $airtime = (int)$data['airtime'];

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

            if($estimatedAirtime >= $airtime - 1 && $estimatedAirtime <= $airtime){
                if($destination->supportsAircraftCode($codeletter)){
                    $suggestedAirports->push($destination);
                    $distances[$destination->icao] = round($distance);
                    $airtimes[$destination->icao] = round($estimatedAirtime, 1);
                }
            }
        }

        $suggestedAirports = $suggestedAirports->sortByDesc('total_score')->slice(1, 10);

        // Fetch TAF
        $tafs = [];
        /*
        foreach($suggestedAirports as $a){
            $response = Http::get('https://api.met.no/weatherapi/tafmetar/1.0/taf.txt?icao='.$a->icao);
            if($response->successful()){
                $data = collect(preg_split("/\r\n|\n|\r/", $response->body()));

                if(empty($response->body())){
                    $tafs[$a->icao] = "N/A";
                    continue;
                }

                $data = $data->filter(function($value, $key){
                    return !empty($value);
                });

                $tafs[$a->icao] = $data->last(); 
            }
        }
        */

        return view('search', compact('suggestedAirports', 'distances', 'airtimes', 'tafs'));
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
