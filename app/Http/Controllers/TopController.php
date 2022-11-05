<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Models\Airport;
use App\Models\AirportScore;

class TopController extends Controller
{
 
    /**
     * List all top airports
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function index(Request $request, string $continent = null){

        $airports = collect();

        if($continent){
            $airportScores = AirportScore::select('airport_id', \DB::raw("count(airport_scores.id) as id_count"))
                                ->groupBy('airport_id')
                                ->orderByDesc('id_count')
                                ->join('airports', 'airport_scores.airport_id', '=', 'airports.id')
                                ->where('airports.continent', $continent)
                                ->whereIn('airports.type', ['large_airport','medium_airport','seaplane_base','small_airport'])
                                ->with('airport', 'airport.metar', 'airport.runways', 'airport.scores')
                                ->limit(30)
                                ->get();
            $airports = collect();
            foreach($airportScores as $as){
                $airports->push($as->airport);
            }

        } else {
            $airportScores = AirportScore::select('airport_id', \DB::raw("count(airport_scores.id) as id_count"))
                                ->groupBy('airport_id')->orderByDesc('id_count')
                                ->join('airports', 'airport_scores.airport_id', '=', 'airports.id')
                                ->whereIn('airports.type', ['large_airport','medium_airport','seaplane_base','small_airport'])
                                ->with('airport', 'airport.metar', 'airport.runways', 'airport.scores')
                                ->limit(30)
                                ->get();
            
            $airports = collect();
            foreach($airportScores as $as){
                $airports->push($as->airport);
            }
        }
        
        return view('top', compact('airports', 'continent'));
    }

}
