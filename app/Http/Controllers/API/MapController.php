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
        $this->sortSceneries($returnData);

        // 5. Return response
        if (!empty($returnData)) {
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
            'msfs2020' => Simulator::find(1),
            'msfs2024' => Simulator::find(11),
        ];

        // Decode FSAddonCompare sceneries
        $fsacSceneries = collect(json_decode($fsacResponse->body(), false)->results);
        $fsacSceneryDevelopers = $fsacSceneries->pluck('developer');

        // Remove sceneries already in our DB
        $w2fSceneries = Scenery::where('icao', $airportIcao)->where('published', true)->with('simulators')->get();
        $fsacSceneryDevelopers = $fsacSceneryDevelopers->diff($w2fSceneries->pluck('developer'));

        // Define a blacklist of developers
        $developerBlacklist = collect(['microsoft', 'va systems']);

        // Save new FSAddonCompare sceneries
        foreach ($fsacSceneryDevelopers as $developer) {
            if ($developerBlacklist->contains(strtolower($developer))) {
                continue;
            }

            $fsacDeveloperScenery = $fsacSceneries->firstWhere('developer', $developer);
            $stores = collect($fsacDeveloperScenery->prices)->where('isDeveloper', true)
                ?? collect($fsacDeveloperScenery->prices)->where(fn ($price) => collect(['simmarket.com', 'aerosoft.com', 'orbxdirect.com', 'flightsim.to'])->contains(fn ($domain) => strpos($price->link, $domain) !== false));

            if (! $stores || $stores->count() === 0) {
                continue;
            }

            $sceneryModel = Scenery::create([
                'icao' => strtoupper($airportIcao),
                'developer' => $developer,
                'link' => 'N/A', // to be removed
                'airport_id' => Airport::where('icao', $airportIcao)->first()->id,
                'payware' => true, // to be removed
                'published' => true,
                // should we add created/updated timestamp here as well?
            ]);
 
            // Attach simulators to correct store link(s)
            foreach($stores as $store){
                foreach($supportedSimulators as $supportedSim){
                    if(in_array($supportedSim->shortened_name, $store->simulatorVersions)){
                        $sceneryModel->simulators()->attach($supportedSim, [
                            'link' => $this->getEmbeddedUrl($store->link),
                            'payware' => $store->currencyPrice->EUR > 0,
                        ]);
                    }
                }
            }
        }

        // Update FSAddonCompare sceneries if new simulatorVersions are available
        foreach ($fsacSceneries as $scenery) {
            $sceneryModel = Scenery::where('developer', $scenery->developer)->where('icao', $airportIcao)->first();
            if ($sceneryModel) {
                $storedSimulators = $sceneryModel->simulators->pluck('shortened_name')->toArray();
                $newSimulators = $scenery->simulatorVersions;

                if (array_diff($newSimulators, $storedSimulators) || array_diff($storedSimulators, $newSimulators)) {
                    $sceneryModel->simulators()->detach();
                    $sceneryModel->simulators()->attach(Simulator::whereIn('shortened_name', $newSimulators)->get());
                }
            }
        }

        // Add FSAddonCompare sceneries to $returnData
        foreach ($fsacSceneries as $scenery) {
            if ($developerBlacklist->contains(strtolower($scenery->developer))) {
                continue;
            }

            foreach($supportedSimulators as $supportedSim){
                if(in_array($supportedSim->shortened_name, $scenery->simulatorVersions)){
                    $cheapestStore = collect($scenery->prices)
                        ->filter(fn($store) => $store->simulatorVersions && collect($store->simulatorVersions)->contains($supportedSim->shortened_name))
                        ->sortBy('currencyPrice.EUR')
                        ->first();
                    $returnData[$supportedSim->shortened_name][] = $this->prepareSceneryData($scenery, $cheapestStore);
                }
            }
        }

        // Add our own sceneries which were not covered by FSAddonCompare
        $w2fSceneries = Scenery::where('icao', $airportIcao)->where('published', true)->with('simulators')->get();
        foreach ($w2fSceneries as $scenery) {
            foreach ($scenery->simulators as $simulator) {

                if(isset($supportedSimulators[$simulator->shortened_name]) && $fsacSceneries->pluck('developer')->contains($scenery->developer)){
                    continue;
                }
                $returnData[$simulator->shortened_name][] = $this->prepareSceneryData($scenery);
            }
        }

        return $returnData;
    }

    /**
     * Function to prepare scenery data
     */
    private function prepareSceneryData($scenery, $store = null){
        return [
            'id' => $scenery->id ?? null,
            'developer' => $scenery->developer,
            'link' => $scenery->link,
            'linkDomain' => $store ? null : parse_url($scenery->link, PHP_URL_HOST),
            'currencyLink' => $store->currencyLink ?? null,
            'cheapestLink' => $store->link ?? $scenery->link,
            'cheapestStore' => $store->store ?? $scenery->developer,
            'cheapestPrice' => $store->currencyPrice ?? null,
            'ratingAverage' => $scenery->ratingAverage ?? null,
            'payware' => (int) ($store ? $store->currencyPrice->EUR > 0 : $scenery->payware),
            'fsac' => (bool) $store,
        ];
    }

    /**
     * Handle FSAddonCompare failure or timeout by returning sceneries from the cache instead
     */
    private function handleFsacFailure($airportIcao)
    {
        $returnData = [];

        $sceneries = Scenery::where('icao', $airportIcao)->where('published', true)->with('simulators')->get();
        foreach ($sceneries as $scenery) {
            foreach ($scenery->simulators as $simulator) {
                $returnData[$simulator->shortened_name][] = [
                    'developer' => $scenery->developer,
                    'link' => ($scenery->source == 'fsaddoncompare')
                        ? "https://www.fsaddoncompare.com/search/{$scenery->icao}?utm_campaign=WhereToFly"
                        : $scenery->link,
                    'linkDomain' => ($scenery->source == 'fsaddoncompare')
                        ? 'FSAddonCompare'
                        : parse_url($scenery->link, PHP_URL_HOST),
                    'cheapestLink' => $scenery->link,
                    'cheapestStore' => $scenery->developer,
                    'cheapestPrice' => null,
                    'ratingAverage' => null,
                    'payware' => (int) $scenery->payware,
                    'fsac' => false,
                ];
            }
        }

        return $returnData;
    }

    /**
     * Sort the sceneries within each simulator.
     */
    private function sortSceneries(array &$returnData)
    {
        foreach ($returnData as $simulator => $sceneries) {
            // First sort by developer name
            usort($sceneries, fn ($a, $b) => $a['developer'] <=> $b['developer']);
            // Then sort by payware/free
            usort($sceneries, fn ($a, $b) => $a['payware'] <=> $b['payware']);
            $returnData[$simulator] = $sceneries;
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

        // Strip 'www.' and 'secure.' and addoncompare from the URL
        if ($embeddedUrl) {
            $embeddedUrl = str_replace(['www.', 'secure.'], '', $embeddedUrl);
            $embeddedUrl = str_replace('?ref=fsaddoncompare', '', $embeddedUrl);
        }

        return $embeddedUrl;
    }
}
