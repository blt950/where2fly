<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Models\Airport;

class TopController extends Controller
{
    
    public function index(Request $request, string $continent = null){

        $airports = collect();

        if($continent){
            $airports = Airport::where('continent', $continent)->orderBy('total_score', 'DESC')->with('scores', 'metar', 'runways')->limit(30)->get();
        } else {
            $airports = Airport::orderBy('total_score', 'DESC')->with('scores', 'metar', 'runways')->limit(30)->get();
        }

        // Fetch TAF
        $tafs = [];
        /*
        
        foreach($airports as $a){
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
        }*/
        
        return view('top', compact('airports', 'continent', 'tafs'));
    }

}
