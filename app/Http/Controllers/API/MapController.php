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
use App\Models\SceneryDeveloper;

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
        // Prepare references
        $returnData = [];
        $supportedApiSimulators = [
            'MSFS2020' => Simulator::find(1),
            'MSFS2024' => Simulator::find(11),
        ];

        // Run through results and decide actions
        $fsacSceneries = collect(json_decode($fsacResponse->body(), false)->results);
        foreach($fsacSceneries as $scenery){

            // Get id for the product
            $fsacId = $scenery->id;

            // Skip blacklisted developers (due to irrelevant addons or duplicates)
            if(in_array(strtolower($scenery->developer), ['microsoft', 'va systems'])){
                continue;
            }

            // Find developer in question, or create them
            $sceneryDeveloperModel = SceneryDeveloper::where('icao', $airportIcao)->where('developer', $scenery->developer)->with('sceneries')->first();
            if($sceneryDeveloperModel == null){
                // Let's create the scenery developer that doesn't exist
                $sceneryDeveloperModel = SceneryDeveloper::create([
                    'icao' => strtoupper($airportIcao),
                    'developer' => $scenery->developer,
                    'airport_id' => Airport::where('icao', $airportIcao)->first()->id,
                ]);
            }

            // Find the cheapest store and official or market store for the scenery
            $cheapestStore = SceneryHelper::findCheapestStore($scenery->prices);
            $officialOrMarketStoreLink = SceneryHelper::findOfficialOrMarketStore($scenery->prices, $scenery->developer);

            // Loop through all supported simulators and create or update the sceneries table
            foreach($scenery->simulatorVersions as $compatibleSimulator){

                $sceneryModel = $sceneryDeveloperModel->sceneries->where('simulator_id', $supportedApiSimulators[$compatibleSimulator]->id)->where('source_reference_id', $fsacId)->first();
                if($sceneryModel == null){
                    // Let's create the scenery that doesn't exist
                    $sceneryModel = Scenery::create([
                        'scenery_developer_id' => $sceneryDeveloperModel->id,
                        'simulator_id' => $supportedApiSimulators[$compatibleSimulator]->id,
                        'link' => $officialOrMarketStoreLink,
                        'payware' => $cheapestStore->currencyPrice->EUR > 0,
                        'published' => true,
                        'source' => 'fsaddoncompare',
                        'source_reference_id' => $fsacId,
                    ]);
                } else {
                    // Check if the link has changed and update it
                    if($sceneryModel->link != $officialOrMarketStoreLink){
                        $sceneryModel->link = $officialOrMarketStoreLink;
                        $sceneryModel->save();
                    }
                }

                // Add scenery to return data
                $returnData[$supportedApiSimulators[$compatibleSimulator]->shortened_name][] = SceneryHelper::prepareSceneryData($sceneryDeveloperModel, $sceneryModel, [
                    'link' => $officialOrMarketStoreLink,
                    'currencyLink' => $cheapestStore->currencyLink,
                    'cheapestLink' => $cheapestStore->link,
                    'cheapestStore' => $cheapestStore->store,
                    'cheapestPrice' => $cheapestStore->currencyPrice,
                    'ratingAverage' => $scenery->ratingAverage,
                    'payware' => $cheapestStore->currencyPrice->EUR > 0,
                    'fsac' => true,
                ]);
            }

        }

        // Add our own local sceneries which are not covered by FSAddonCompare
        $w2fDevelopers = SceneryDeveloper::where('icao', $airportIcao)->with('sceneries', 'sceneries.simulator')->get();
        foreach($w2fDevelopers as $sceneryDeveloperModel){
            foreach($sceneryDeveloperModel->sceneries->whereNull('source_reference_id') as $sceneryModel){
                $returnData[$sceneryModel->simulator->shortened_name][] = SceneryHelper::prepareSceneryData($sceneryDeveloperModel, $sceneryModel);
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
