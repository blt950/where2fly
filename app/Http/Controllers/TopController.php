<?php

namespace App\Http\Controllers;

use App\Helpers\AircraftHelper;
use App\Helpers\MapHelper;
use App\Models\AirportScore;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TopController extends Controller
{
    public const CONTINENTS = [
        'AF' => 'Africa',
        'AS' => 'Asia',
        'EU' => 'Europe',
        'NA' => 'North America',
        'OC' => 'Oceania',
        'SA' => 'South America',
    ];

    /**
     * List all top airports
     */
    public function index(Request $request, ?string $continent = null): View
    {
        $exclude = $request->input('exclude');
        if(isset($continent)){
            $continent = strtoupper($continent);
        }

        // Defaults to JM; 'all' explicitly disables the aircraft filter
        $aircraft = $request->input('aircraft', 'JM');
        if ($aircraft === 'all') {
            $aircraft = null;
        } elseif (! AircraftHelper::isValidCode($aircraft)) {
            $aircraft = 'JM';
        }

        $airportScores = AirportScore::getTopAirports($continent, null, 30, $exclude, $aircraft);

        $airports = collect();
        foreach ($airportScores as $as) {
            $airports->push($as->airport);
        }

        $airportMapData = json_encode(MapHelper::generateAirportMapDataFromAirports($airports));

        return view('top', compact('airports', 'airportMapData', 'continent', 'exclude', 'aircraft'));
    }
}
