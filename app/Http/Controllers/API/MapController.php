<?php

namespace App\Http\Controllers\API;

use App\Helpers\MapHelper;
use App\Http\Controllers\Controller;
use App\Models\Airline;
use App\Models\Airport;
use App\Models\Flight;
use App\Models\Scenery;
use App\Models\Simulator;
use App\Models\UserList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class MapController extends Controller
{
    /**
     * Check if user is authenticated
     */
    public function isAuthenticated(Request $request)
    {
        $loggedIn = Auth::guard('sanctum')->check();

        return response()->json(['message' => 'Authenticated', 'data' => $loggedIn], 200);
    }

    /**
     * Get airports from lists
     */
    public function getListAirports()
    {
        $userLists = UserList::where('user_id', Auth::id())->with('airports')->get();

        $airportsMapCollection = MapHelper::getAirportsFromUserLists($userLists);
        $airportMapData = MapHelper::generateAirportMapDataFromAirports($airportsMapCollection);

        return response()->json(['message' => 'Success', 'data' => $airportMapData], 200);
    }

    /**
     * Get airport map data from ICAO
     */
    public function getMapdataFromIcao(Request $request)
    {
        $data = request()->validate([
            'icao' => ['required', 'exists:airports,icao'],
        ]);

        $airport = Airport::where('icao', $data['icao'])->first();
        $airportMapData = MapHelper::generateAirportMapDataFromAirports(collect([$airport]));

        if (isset($airport)) {
            return response()->json(['message' => 'Success', 'data' => $airportMapData], 200);
        } else {
            return response()->json(['message' => 'Airport not found'], 404);
        }
    }

    /**
     * Get the airport list for airport card
     */
    public function getAirport(Request $request)
    {
        $data = request()->validate([
            'primaryAirport' => ['nullable', 'exists:airports,id'],
            'secondaryAirport' => ['required', 'exists:airports,id'],
            'reverseDirection' => ['nullable'],
            'highlightedAircrafts' => ['nullable', 'array'],
        ]);

        $primaryAirport = $data['primaryAirport'];
        $secondaryAirport = $data['secondaryAirport'];
        $direction = $data['reverseDirection'];
        $highlightedAircrafts = isset($data['highlightedAircrafts']) ? collect($data['highlightedAircrafts']) : null;

        // If direction is set, let's search for airlines
        $airlines = null;
        if (isset($direction)) {
            $flights = null;
            $arrivalAirportColumn = $direction == true ? 'airport_dep_id' : 'airport_arr_id';
            $departureAirportColumn = $direction == true ? 'airport_arr_id' : 'airport_dep_id';

            // Get flights and airlines for the suggested airports
            $flights = Flight::select('id', 'airline_icao')->where('seen_counter', '>', 3)->where($arrivalAirportColumn, $secondaryAirport)->where($departureAirportColumn, $primaryAirport)->with('aircrafts')->get();
            $airlines = Airline::whereIn('icao_code', $flights->pluck('airline_icao')->unique())->get();

            // Highlight airlines that have the aircrafts in the list
            if (isset($highlightedAircrafts)) {
                foreach ($flights as $flight) {
                    foreach ($flight->aircrafts as $aircraft) {
                        if ($highlightedAircrafts->contains($aircraft->icao)) {
                            $airlineIcao = $airlines->where('icao_code', $flight->airline_icao);
                            if ($airlineIcao && $airlineIcao->count() > 0) {
                                $airlineIcao->first()->highlighted = true;
                            }
                        }
                    }
                }
            }

            // Replace * with '' in all airline iata codes
            foreach ($airlines as $airline) {
                $airline->iata_code = str_replace('*', '', $airline->iata_code);
            }
        }

        $airport = Airport::select('id', 'icao', 'name', 'iso_country')->with(['runways' => function ($query) {
            $query->where('closed', false)->whereNotNull('length_ft');
        }])->where('id', $secondaryAirport)->first();
        $metar = isset($airport->metar) ? $airport->metar->metar : null;

        if (isset($airport)) {
            return response()->json(['message' => 'Success', 'data' => [
                'airport' => $airport->toArray(),
                'metar' => $metar,
                'airlines' => $airlines,
            ]], 200);
        } else {
            return response()->json(['message' => 'Airport not found'], 404);
        }
    }

    /**
     * Get flights for the airport pair and given airline
     */
    public function getFlights(Request $request)
    {
        $data = request()->validate([
            'departureAirportId' => ['required', 'exists:airports,id'],
            'arrivalAirportId' => ['required', 'exists:airports,id'],
            'airlineId' => ['required', 'exists:airlines,icao_code'],
            'highlightedAircrafts' => ['nullable', 'array'],
        ]);

        $departureAirportId = $data['departureAirportId'];
        $arrivalAirportId = $data['arrivalAirportId'];
        $airlineIcao = $data['airlineId'];
        $highlightedAircrafts = isset($data['highlightedAircrafts']) ? collect($data['highlightedAircrafts']) : null;

        $airline = Airline::where('icao_code', $airlineIcao)->first();
        $flights = Flight::where('seen_counter', '>', 3)->where('airport_dep_id', $departureAirportId)->where('airport_arr_id', $arrivalAirportId)->where('airline_icao', $airlineIcao)->with('aircrafts')->orderByDesc('last_seen_at')->get();

        // Highlight airlines that have the aircrafts in the list
        if (isset($highlightedAircrafts)) {
            foreach ($flights as $flight) {
                foreach ($flight->aircrafts as $aircraft) {
                    if ($highlightedAircrafts->contains($aircraft->icao)) {
                        $flight->highlighted = true;
                    }
                }
            }
        }

        $airline->iata_code = str_replace('*', '', $airline->iata_code);

        if (isset($flights)) {
            return response()->json(['message' => 'Success', 'data' => [
                'flights' => $flights,
                'airline' => $airline,
            ]], 200);
        } else {
            return response()->json(['message' => 'Flights not found'], 404);
        }
    }

    /**
     * Get scenery
     */
    public function getScenery(Request $request)
    {
        $airportIcao = request()->validate([
            'airportIcao' => ['required', 'exists:airports,icao'],
        ])['airportIcao'];

        $returnData = [];
        $fsacResponse = Http::withHeaders([
            'Authorization' => config('app.fsaddoncompare_key'),
        ])->timeout(5)->get('https://api.fsaddoncompare.com/partner/search/icao/' . strtoupper($airportIcao));

        if ($fsacResponse->successful()) {
            $msfs = Simulator::find(1);
            $fsacSceneries = collect(json_decode($fsacResponse->body(), false)->results);
            $fsacSceneryDevelopers = $fsacSceneries->pluck('developer');

            // Remove sceneries already in our database
            $w2fSceneries = Scenery::where('icao', $airportIcao)->where('published', true)->with('simulators')->get();
            $fsacSceneryDevelopers = $fsacSceneryDevelopers->diff($w2fSceneries->pluck('author'));

            // Define a blacklist of developers
            $developerBlacklist = collect(['microsoft', 'va systems']);

            // Save new FSAddonCompare sceneries
            foreach ($fsacSceneryDevelopers as $developer) {
                if ($developerBlacklist->contains(strtolower($developer))) {
                    continue;
                }

                $fsacScenery = $fsacSceneries->firstWhere('developer', $developer);
                $store = collect($fsacScenery->prices)->firstWhere('isDeveloper', true) ??
                         collect($fsacScenery->prices)->first(fn ($price) => in_array(parse_url($price->link, PHP_URL_HOST), ['simmarket.com', 'aerosoft.com', 'orbxdirect.com', 'flightsim.to']));

                if (! $store) {
                    continue;
                }

                $sceneryModel = Scenery::create([
                    'icao' => strtoupper($airportIcao),
                    'author' => $developer,
                    'link' => $this->getEmbeddedUrl($store->link),
                    'airport_id' => Airport::where('icao', $airportIcao)->first()->id,
                    'payware' => $store->currencyPrice->EUR > 0,
                    'published' => true,
                ]);

                $sceneryModel->simulators()->attach($msfs);
            }

            // Prepare return data
            $prepareSceneryData = function ($scenery, $store = null) {
                return [
                    'developer' => $scenery->developer ?? $scenery->author,
                    'link' => $scenery->link,
                    'linkDomain' => $store ? null : parse_url($scenery->link, PHP_URL_HOST),
                    'currencyLink' => $store->currencyLink ?? null,
                    'cheapestLink' => $store->link ?? $scenery->link,
                    'cheapestStore' => $store->store ?? $scenery->author,
                    'cheapestPrice' => $store->currencyPrice ?? null,
                    'ratingAverage' => $scenery->ratingAverage ?? null,
                    'payware' => (int) ($store ? $store->currencyPrice->EUR > 0 : $scenery->payware),
                    'fsac' => (bool) $store,
                ];
            };

            // Add FSAddonCompare sceneries
            foreach ($fsacSceneries as $scenery) {
                if ($developerBlacklist->contains(strtolower($scenery->developer))) {
                    continue;
                }

                $cheapestStore = collect($scenery->prices)->sortBy('currencyPrice.EUR')->first();
                $returnData[$msfs->shortened_name][] = $prepareSceneryData($scenery, $cheapestStore);
            }

            // Add our own sceneries
            foreach ($w2fSceneries->whereNotIn('author', $fsacSceneries->pluck('developer')) as $scenery) {
                foreach ($scenery->simulators as $simulator) {
                    $returnData[$simulator->shortened_name][] = $prepareSceneryData($scenery);
                }
            }

        } else {
            // If FSAddonCompare API doesn't work, just return our own database
            $sceneries = Scenery::where('icao', $airportIcao)->where('published', true)->with('simulators')->get();

            foreach ($sceneries as $scenery) {
                foreach ($scenery->simulators as $simulator) {
                    $returnData[$simulator->shortened_name][] = [
                        'developer' => $scenery->author,
                        'link' => $scenery->link,
                        'linkDomain' => parse_url($scenery->link, PHP_URL_HOST),
                        'cheapestLink' => $scenery->link,
                        'cheapestStore' => $scenery->author,
                        'cheapestPrice' => null,
                        'ratingAverage' => null,
                        'payware' => (int) $scenery->payware,
                        'fsac' => false,
                    ];
                }
            }
        }

        // Sort the sceneries within each simulator. First by alphabetical order, then by payware/free
        foreach ($returnData as $simulator => $sceneries) {
            usort($sceneries, fn ($a, $b) => $a['developer'] <=> $b['developer']);
            usort($sceneries, fn ($a, $b) => $a['payware'] <=> $b['payware']);
            $returnData[$simulator] = $sceneries;
        }

        if (isset($returnData) && count($returnData) > 0) {
            return response()->json(['message' => 'Success', 'data' => $returnData], 200);
        } else {
            return response()->json(['message' => 'Scenery not found'], 404);
        }
    }

    private function getEmbeddedUrl($fullUrl)
    {
        // First, decode the URL (if necessary)
        $decodedUrl = urldecode($fullUrl);

        // Parse the query part of the URL
        $urlComponents = parse_url($decodedUrl);

        // Parse the query string into an associative array
        parse_str($urlComponents['query'], $queryParams);

        // Retrieve the 'url' parameter value
        $embeddedUrl = isset($queryParams['url']) ? $queryParams['url'] : null;

        // Strip 'www.' and 'secure.' from the URL
        if ($embeddedUrl) {
            $embeddedUrl = str_replace(['www.', 'secure.'], '', $embeddedUrl);
        }

        return $embeddedUrl;
    }
}
