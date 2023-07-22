<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
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

        $airportScores = AirportScore::getTopAirports($continent);
        $airports = collect();
        foreach($airportScores as $as){
            $airports->push($as->airport);
        }
        
        return view('top', compact('airports', 'continent'));
    }

}
