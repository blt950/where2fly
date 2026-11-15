<?php

namespace App\Http\Controllers;

use App\Helpers\AircraftHelper;
use App\Helpers\MapHelper;
use App\Helpers\ScoreHelper;
use App\Models\Airport;
use App\Models\AirportScore;
use App\View\Components\ScoreIcon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

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
    public function index(Request $request, ?string $continent = null): Response
    {
        $exclude = $request->input('exclude');
        if (isset($continent)) {
            $continent = strtoupper($continent);
        }

        // Defaults to JM; 'all' explicitly disables the aircraft filter
        $aircraft = $request->input('aircraft', 'JM');
        if ($aircraft === 'all') {
            $aircraft = null;
        } elseif (! AircraftHelper::isValidCode($aircraft)) {
            $aircraft = 'JM';
        }

        $airports = AirportScore::getTopAirports($continent, null, 30, $exclude, $aircraft)
            ->pluck('airport');

        return Inertia::render('Top', [
            'airports' => $airports->values()->map(fn (Airport $airport, int $index) => [
                'rank' => $index + 1,
                'icao' => $airport->icao,
                'name' => $airport->name,
                'isoCountry' => $airport->iso_country,
                'countryName' => getCountryName($airport->iso_country),
                'scores' => $airport->displayScores()
                    ->map(fn (AirportScore $score) => (new ScoreIcon($score, $airport))->toArray())
                    ->values(),
            ]),
            'airportMapData' => (object) MapHelper::generateAirportMapDataFromAirports($airports),
            'continent' => $continent,
            'exclude' => $exclude,
            'aircraft' => $aircraft,
            'continents' => self::CONTINENTS,
            'aircraftTypes' => collect(AircraftHelper::TYPES)->map(fn ($type) => ['name' => $type['name']]),
            'scoreTypes' => ScoreHelper::TYPES,
        ])->withViewData([
            'title' => 'Top List',
            'metaDescription' => 'Destinations with the worst weather right now',
        ]);
    }
}
