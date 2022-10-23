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
        
        return view('top', compact('airports', 'continent'));
    }

}
