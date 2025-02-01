<?php

namespace App\Http\Controllers\API;

use App\Helpers\MapHelper;
use App\Helpers\SceneryHelper;
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
        $userLists = UserList::where('user_id', Auth::id())->where('hidden', false)->with('airports')->get();

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

        // Get lists which this airport is present
        $lists = UserList::where('user_id', Auth::id())->where('public', false)->whereHas('airports', function ($query) use ($secondaryAirport) {
            $query->where('airport_id', $secondaryAirport);
        })->get();

        if (isset($airport)) {
            return response()->json(['message' => 'Success', 'data' => [
                'airport' => $airport->toArray(),
                'metar' => $metar,
                'airlines' => $airlines,
                'lists' => $lists,
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
        // 1. Validate input
        $airportIcao = request()->validate([
            'airportIcao' => ['required', 'exists:airports,icao'],
        ])['airportIcao'];

        // 2. Fetch FSAddonCompare sceneries
        $fsacResponse = $this->fetchFsacSceneries($airportIcao);

        // 3. Decide how to handle response
        if ($fsacResponse->successful()) {
            $returnData = $this->handleSuccessfulFsacResponse($fsacResponse, $airportIcao);
        } else {
            // If FSAddonCompare API fails, get local sceneries
            $returnData = $this->handleFsacFailure($airportIcao);
        }

        // 4. Sort sceneries
        SceneryHelper::sortSceneries($returnData);

        // 5. Return response
        if (! empty($returnData)) {
            return response()->json(['message' => 'Success', 'data' => $returnData], 200);
        } else {
            return response()->json(['message' => 'Scenery not found'], 404);
        }
    }

    /**
     * Fetch FSAddonCompare sceneries
     */
    private function fetchFsacSceneries($airportIcao)
    {
        return Http::withHeaders([
            'Authorization' => config('app.fsaddoncompare_key'),
        ])->timeout(5)->get('https://api.fsaddoncompare.com/partner/search/icao/' . strtoupper($airportIcao));
    }

    /**
     * Handle successful FSAddonCompare response
     */
    private function handleSuccessfulFsacResponse($fsacResponse, $airportIcao)
    {
        $returnData = [];

        // Prepare references
        $supportedSimulators = [
            'MSFS2020' => Simulator::find(1),
            'MSFS2024' => Simulator::find(11),
        ];

        // Decode FSAddonCompare sceneries
        $fsacSceneries = collect(json_decode($fsacResponse->body(), false)->results);
        $fsacSceneryDevelopers = $fsacSceneries->pluck('developer');

        // Remove sceneries already in our DB
        $w2fSceneries = Scenery::where('icao', $airportIcao)->get();
        $fsacSceneryDevelopers = $fsacSceneryDevelopers->diff($w2fSceneries->pluck('developer'));

        // Define a blacklist of developers
        $developerBlacklist = collect(['microsoft', 'va systems']);

        // Save new FSAddonCompare sceneries to cache
        foreach ($fsacSceneryDevelopers as $developer) {
            if ($developerBlacklist->contains(strtolower($developer))) {
                continue;
            }

            $sceneryModel = Scenery::create([
                'icao' => strtoupper($airportIcao),
                'developer' => $developer,
                'airport_id' => Airport::where('icao', $airportIcao)->first()->id,
            ]);

            // Attach simulators to correct store link(s)
            $stores = SceneryHelper::findOfficialOrMarketStore($fsacSceneries, $developer);
            if ($stores) {
                SceneryHelper::attachSimulators($stores, $supportedSimulators, $sceneryModel);
            }

        }

        // Update FSAddonCompare sceneries if new simulatorVersions are available and add link and payware to the pivot table
        foreach ($fsacSceneries as $scenery) {
            $sceneryModel = Scenery::where('developer', $scenery->developer)->where('icao', $airportIcao)->first();
            if ($sceneryModel) {
                $storedSimulators = $sceneryModel->simulators->pluck('shortened_name')->toArray();
                $newSimulators = $scenery->simulatorVersions;

                if (array_diff($newSimulators, $storedSimulators) || array_diff($storedSimulators, $newSimulators)) {
                    $stores = SceneryHelper::findOfficialOrMarketStore($fsacSceneries, $scenery->developer);
                    if ($stores) {
                        $sceneryModel->simulators()->detach();
                        SceneryHelper::attachSimulators($stores, $supportedSimulators, $sceneryModel);
                    }
                }
            }
        }

        // Add FSAddonCompare sceneries to $returnData
        foreach ($fsacSceneries as $scenery) {
            if ($developerBlacklist->contains(strtolower($scenery->developer))) {
                continue;
            }

            foreach ($supportedSimulators as $supportedSim) {
                if (in_array($supportedSim->shortened_name, $scenery->simulatorVersions)) {
                    $cheapestStore = collect($scenery->prices)
                        ->filter(function ($store) use ($scenery, $supportedSim) {
                            $storeVersions = $store->simulatorVersions ?? $scenery->simulatorVersions;
                            return $storeVersions && collect($storeVersions)->contains($supportedSim->shortened_name);
                        })
                        ->sortBy('currencyPrice.EUR')
                        ->first();

                    $returnData[$supportedSim->shortened_name][] = SceneryHelper::prepareSceneryData($scenery, $cheapestStore);
                }
            }
        }

        // Add our own sceneries which were not covered by FSAddonCompare
        $w2fSceneries = Scenery::withPublished(true)->where('icao', $airportIcao)->get();
        foreach ($w2fSceneries as $scenery) {
            foreach ($scenery->simulators as $scenerySimulator) {

                if (isset($supportedSimulators[$scenerySimulator->shortened_name]) && $fsacSceneries->pluck('developer')->contains($scenery->developer)) {
                    continue;
                }
                $returnData[$scenerySimulator->shortened_name][] = SceneryHelper::prepareSceneryData($scenery, null, $scenerySimulator);
            }
        }

        return $returnData;
    }

    /**
     * Handle FSAddonCompare failure or timeout by returning sceneries from the cache instead
     */
    private function handleFsacFailure($airportIcao)
    {
        $returnData = [];

        $sceneries = Scenery::withPublished(true)->where('icao', $airportIcao)->get();
        foreach ($sceneries as $scenery) {
            foreach ($scenery->simulators as $scenerySimulator) {
                $returnData[$scenerySimulator->shortened_name][] = [
                    'developer' => $scenery->developer,
                    'link' => $scenerySimulator->pivot->link,
                    'linkDomain' => parse_url($scenerySimulator->pivot->link, PHP_URL_HOST),
                    'cheapestLink' => $scenerySimulator->pivot->link,
                    'cheapestStore' => $scenerySimulator->pivot->developer,
                    'cheapestPrice' => null,
                    'ratingAverage' => null,
                    'payware' => (int) $scenerySimulator->pivot->payware,
                    'fsac' => false,
                ];
            }
        }

        return $returnData;
    }
}
