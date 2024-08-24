<?php

namespace App\Http\Controllers;

use App\Helpers\MapHelper;
use App\Models\AirportScore;
use App\Models\Scenery;
use Illuminate\Http\Request;

class TopController extends Controller
{
    /**
     * List all top airports
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function index(Request $request, ?string $continent = null)
    {
        $exclude = $request->input('exclude');
        $airportScores = AirportScore::getTopAirports($continent, null, 30, $exclude);

        $airports = collect();
        foreach ($airportScores as $as) {
            $airports->push($as->airport);
        }

        $airportMapData = MapHelper::generateAirportMapDataFromAirports($airports);
        $sceneriesCollection = Scenery::where('published', true)->whereIn('airport_id', $airports->pluck('id'))->with('simulator')->get();

        return view('top', compact('airports', 'airportMapData', 'continent', 'exclude', 'sceneriesCollection'));
    }
}
