<?php

namespace App\Http\Controllers;

use App\Helpers\AircraftHelper;
use App\Helpers\CalculationHelper;
use App\Helpers\CountryHelper;
use App\Helpers\ScoreHelper;
use App\Models\Aircraft;
use App\Models\Airline;
use App\Models\Airport;
use App\Models\Flight;
use App\Models\UserList;
use App\Rules\AirportExists;
use App\Rules\FlightDirection;
use App\Rules\ValidAircrafts;
use App\Rules\ValidAirlines;
use App\Rules\ValidDestinations;
use App\Rules\ValidScores;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class SearchController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function indexArrivalSearch()
    {
        return $this->buildSearchView('front.arrivals');
    }

    /**
     * Display a listing of the resource.
     */
    public function indexDepartureSearch()
    {
        return $this->buildSearchView('front.departures');
    }

    /**
     * Build the shared view data for the arrival/departure search forms.
     */
    private function buildSearchView(string $view): View
    {
        $airlines = Airline::where('has_flights', true)->orderBy('name')->get();
        $aircrafts = Aircraft::all()->pluck('icao')->sort();
        $prefilledIcao = request()->input('icao');
        $destinationInputs = $this->getDestinationInputs();
        $whitelistDatabase = null;

        $lists = UserList::where('public', true)
            ->when(Auth::check(), fn ($q) => $q->orWhere('user_id', Auth::id()))
            ->get();

        if (old('whitelists') !== null) {
            $whitelistDatabase = $this->getWhitelistsFromInput(old('whitelists'));
        }

        return view($view, compact('airlines', 'aircrafts', 'prefilledIcao', 'lists', 'destinationInputs', 'whitelistDatabase'));
    }

    /**
     * Display a listing of the resource.
     */
    public function indexRouteSearch()
    {
        return view('front.routes');
    }

    /**
     * Search for a flight
     *
     * @return RedirectResponse
     */
    public function search(Request $request)
    {

        /**
         *  Validate the request and mapping of arguments
         */
        $validator = Validator::make($request->all(), [
            'icao' => ['nullable', new AirportExists],
            'direction' => ['required', 'in:arrival,departure'],
            'destinations' => ['sometimes', 'array', new ValidDestinations],
            'destinationExclusions' => ['sometimes', 'array', new ValidDestinations],
            'codeletter' => ['required', 'string', 'in:' . implode(',', AircraftHelper::codes())],
            'airtimeMin' => ['required', 'numeric', 'between:0,12'],
            'airtimeMax' => ['required', 'numeric', 'between:0,12'],
            'distanceMin' => ['sometimes', 'numeric', 'between:0,6000'],
            'distanceMax' => ['sometimes', 'numeric', 'between:0,6000'],
            'sortByWeather' => ['in:0,1'],
            'sortByATC' => ['in:0,1'],
            'whitelists' => ['sometimes', 'array'],
            'scores' => ['sometimes', 'array', new ValidScores],
            'metcondition' => ['required', 'in:IFR,VFR,ANY'],
            'destinationWithRoutesOnly' => ['required', 'numeric', 'between:-1,1'],
            'destinationRunwayLights' => ['required', 'numeric', 'between:-1,1'],
            'destinationAirbases' => ['required', 'numeric', 'between:-1,1'],
            'flightDirection' => ['required', new FlightDirection],
            'destinationAirportSize' => ['sometimes', 'array', 'in:small_airport,medium_airport,large_airport'],
            'temperatureMin' => ['required', 'numeric', 'between:-60,60'],
            'temperatureMax' => ['required', 'numeric', 'between:-60,60'],
            'elevationMin' => ['required', 'numeric', 'between:-2000,18000'],
            'elevationMax' => ['required', 'numeric', 'between:-2000,18000'],
            'rwyLengthMin' => ['required', 'numeric', 'between:0,17000'],
            'rwyLengthMax' => ['required', 'numeric', 'between:0,17000'],
            'airlines' => ['sometimes', 'array', new ValidAirlines],
            'aircrafts' => ['sometimes', 'array', new ValidAircrafts],
            'searchVersion' => ['sometimes', 'numeric', 'in:' . config('app.searchVersion')],
        ]);

        if ($validator->fails()) {
            if (isset($validator->getData()['direction']) && $validator->getData()['direction'] == 'arrival') {
                return redirect(route('front.departures'))->withErrors($validator)->withInput();
            }

            return redirect(route('front'))->withErrors($validator)->withInput();
        }

        $data = $validator->validated();

        // If scores are not set, initialize it to an empty array
        if (! isset($data['scores'])) {
            $data['scores'] = [];
        }

        $direction = $data['direction'];
        $destinations = isset($data['destinations']) ? $this->filterDestinations($data['destinations']) : $this->filterDestinations(['Anywhere']);
        $destinationExclusions = isset($data['destinationExclusions']) ? $this->filterDestinations($data['destinationExclusions']) : $this->filterDestinations(['Anywhere']);
        $codeletter = $data['codeletter'];
        $airtimeMin = (int) $data['airtimeMin'];
        $airtimeMax = (int) $data['airtimeMax'];
        if ($airtimeMax == 12) {
            $airtimeMax = 24;
        } // If airtime is 12+ hours, bump it

        // Optional so pre-existing bookmarked searches keep validating
        $distanceMin = (int) ($data['distanceMin'] ?? 0);
        $distanceMax = (int) ($data['distanceMax'] ?? 6000);

        // Create a filter array based on input
        $sortByScores = [];
        isset($data['sortByWeather']) ? $sortByScores = array_merge($sortByScores, ScoreHelper::weatherTypes()) : null;
        isset($data['sortByATC']) ? $sortByScores = array_merge($sortByScores, ScoreHelper::vatsimTypes()) : null;

        $whitelist = null;
        if (isset($data['whitelists'])) {
            $whitelist = UserList::whereIn('id', $data['whitelists'])->get();
            $whitelist = $whitelist->pluck('airports')->flatten()->pluck('icao')->unique()->toArray();
        }

        $filterByScores = array_map('intval', $data['scores']);

        $metcon = $data['metcondition'];
        $destinationWithRoutesOnly = (int) $data['destinationWithRoutesOnly'];
        $destinationRunwayLights = (int) $data['destinationRunwayLights'];
        $destinationAirbases = (int) $data['destinationAirbases'];
        ($data['flightDirection'] != 0) ? $flightDirection = $data['flightDirection'] : $flightDirection = null;

        (isset($data['destinationAirportSize']) && ! empty($data['destinationAirportSize'])) ? $destinationAirportSize = $data['destinationAirportSize'] : $destinationAirportSize = ['small_airport', 'medium_airport', 'large_airport'];

        $temperatureMin = (int) $data['temperatureMin'];
        $temperatureMax = (int) $data['temperatureMax'];
        $elevationMin = (int) $data['elevationMin'];
        $elevationMax = (int) $data['elevationMax'];
        $rwyLengthMin = (int) $data['rwyLengthMin'];
        $rwyLengthMax = (int) $data['rwyLengthMax'];

        $filterByAirlines = $data['airlines'] ?? null;
        $filterByAircrafts = $data['aircrafts'] ?? null;

        [$minDistance, $maxDistance] = CalculationHelper::aircraftNmPerHourRange($codeletter, $airtimeMin, $airtimeMax);

        // Intersect the airtime-derived range with the distance slider; the
        // slider's top position means 6000+, i.e. no upper bound
        $minDistance = max($minDistance, $distanceMin);
        if ($distanceMax < 6000) {
            $maxDistance = min($maxDistance, $distanceMax);
        }

        /**
         *  Fetch the requested data
         */

        // Score filters describe the suggested airports, not the anchor. With
        // presence filters (value 1) set, draw a matching target per attempt
        // and pick an anchor within flying range of it.
        $anchorIds = null;
        $scoreTargetIds = null;
        if (! isset($data['icao'])) {
            $anchorPool = fn () => Airport::airportOpen()->isAirportSize($destinationAirportSize)
                ->filterRunwayLengths($rwyLengthMin, $rwyLengthMax, $codeletter)->filterRunwayLights($destinationRunwayLights)
                ->filterAirbases($destinationAirbases)->filterRoutesAndAirlines(null, $filterByAirlines, $filterByAircrafts, $destinationWithRoutesOnly)
                ->returnOnlyWhitelistedIcao($whitelist)
                ->has('metar');

            $presenceScores = array_filter($filterByScores, fn ($value) => $value == 1);

            if (! empty($presenceScores)) {
                // Match targets by reason only — ETA windowing happens in the
                // destination query, once an anchor is drawn
                $scoreTargetIds = $anchorPool()->filterByScores($presenceScores)->pluck('airports.id');
            } else {
                // Pool is identical between attempts — fetch the ids once
                $anchorIds = $anchorPool()->pluck('airports.id');
            }

            if (($scoreTargetIds ?? $anchorIds)->isEmpty()) {
                return back()->withErrors(['airportNotFound' => 'No suitable airport combination could be found with given criteria'])->withInput();
            }
        }

        // Lets find an result with the given criteria. Give it a few attempts before we give up.
        // With a fixed anchor the destination query is deterministic apart from the
        // shuffle — an empty result stays empty, so retrying would only re-run the
        // identical query. Retries only help the random-anchor path, where each
        // attempt draws a new anchor.
        $maxAttempts = isset($data['icao']) ? 1 : 20;
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {

            // Use the supplied departure or draw a random anchor from the pool
            $suggestedAirport = false;
            if (isset($data['icao'])) {
                $primaryAirport = Airport::where('icao', $data['icao'])->orWhere('local_code', $data['icao'])->first();
            } else {
                if ($scoreTargetIds !== null) {
                    // Fresh target and in-range anchor each attempt
                    $target = Airport::find($scoreTargetIds->random());
                    $anchorId = $anchorPool()->notIcao($target->icao)
                        ->withinDistance($target, $minDistance, $maxDistance, $target->icao)
                        ->inRandomOrder()
                        ->value('airports.id');

                    if ($anchorId === null) {
                        continue;
                    }
                } else {
                    $anchorId = $anchorIds->random();
                }

                $primaryAirport = Airport::with('runways', 'scores', 'metar')->find($anchorId);
                $suggestedAirport = true;
            }

            // Calculate the ETA for sorting
            $candidatesAreDepartures = $direction == 'arrival';
            $eta = $candidatesAreDepartures ? now() : CalculationHelper::forecastEtaSql($primaryAirport, $codeletter);

            // Phase 1: fetch the full candidate pool as thin id (+ score_count) rows.
            // Selecting only the id keeps the grouped/sorted temp table in memory
            // (airports.* drags the GEOMETRY column in, forcing it to disk), while
            // the whole pool is still fetched so a refresh can shuffle up a
            // different subset among equally-scored airports.
            $airports = Airport::airportOpen()->notIcao($primaryAirport->icao)->isAirportSize($destinationAirportSize)
                ->inContinent($destinations)->inCountry($destinations, $primaryAirport->iso_country)->inState($destinations)
                ->notInContinent($destinationExclusions)->notInCountry($destinationExclusions, $primaryAirport->iso_country)->notInState($destinationExclusions)
                ->withinDistance($primaryAirport, $minDistance, $maxDistance, $primaryAirport->icao)->withinBearing($primaryAirport, $flightDirection, $minDistance, $maxDistance)
                ->filterRunwayLengths($rwyLengthMin, $rwyLengthMax, $codeletter)->filterRunwayLights($destinationRunwayLights)
                ->filterAirbases($destinationAirbases)->filterByScores($filterByScores, $eta, $candidatesAreDepartures)->filterRoutesAndAirlines($primaryAirport->icao, $filterByAirlines, $filterByAircrafts, $destinationWithRoutesOnly)
                ->returnOnlyWhitelistedIcao($whitelist)
                ->select('airports.id')
                ->sortByScores($sortByScores, $eta, $candidatesAreDepartures)
                ->has('metar')
                ->get();

            // Shuffle within equal-score buckets and limit the results to 20
            $airports = $airports->groupBy('score_count')->map(function ($group) {
                return $group->shuffle();
            })->flatten(1)->take(20);

            // Phase 2: hydrate only the picked airports, preserving the shuffled order
            $airportIds = $airports->pluck('id')->all();
            $scoreCounts = $airports->pluck('score_count', 'id');

            $airports = Airport::with([
                'runways' => function ($query) {
                    $query->where('closed', false)->whereNotNull('length_ft');
                },
                'scores',
                'metar',
                'taf.forecasts',
                'sceneryDevelopers.sceneries' => function ($query) {
                    $query->where('published', true)->with('simulator');
                },
            ])
                ->findMany($airportIds)
                ->sortBy(fn ($airport) => array_search($airport->id, $airportIds))->values()
                ->each(fn ($airport) => $airport->score_count = $scoreCounts->get($airport->id));

            // Filter the eligible airports
            $suggestedAirports = $airports->filterWithCriteria($primaryAirport, $codeletter, $metcon, $temperatureMin, $temperatureMax, $elevationMin, $elevationMax, $candidatesAreDepartures);

            // If max distance is over 1600 and bearing is enabled -> give user warning about inaccuracy
            $bearingWarning = false;
            if ($maxDistance > 2300 && isset($flightDirection)) {
                $bearingWarning = 'Use the destination region filter instead of flight direction for longer hauls, this avoids false positives, skewed or no results.';
            }

            if ($suggestedAirports->count()) {

                // Create an array with all airports coordinates
                $airportCoordinates = [];
                $airportCoordinates[$primaryAirport->icao]['id'] = $primaryAirport->id;
                $airportCoordinates[$primaryAirport->icao]['icao'] = $primaryAirport->icao;
                $airportCoordinates[$primaryAirport->icao]['lat'] = $primaryAirport->coordinates->latitude;
                $airportCoordinates[$primaryAirport->icao]['lon'] = $primaryAirport->coordinates->longitude;
                $airportCoordinates[$primaryAirport->icao]['type'] = $primaryAirport->type;

                // Lets add the coordinates of the suggested airports
                foreach ($suggestedAirports as $airport) {
                    $airportCoordinates[$airport->icao]['id'] = $airport->id;
                    $airportCoordinates[$airport->icao]['icao'] = $airport->icao;
                    $airportCoordinates[$airport->icao]['lat'] = $airport->coordinates->latitude;
                    $airportCoordinates[$airport->icao]['lon'] = $airport->coordinates->longitude;
                    $airportCoordinates[$airport->icao]['type'] = $airport->type;
                    $airportCoordinates[$airport->icao]['color'] = 'grey';
                }

                // The primary airport's scores are windowed at now(); when it's the
                // departure airport, the current METAR is its weather truth and TAFs are ignored
                [$primaryScores] = $primaryAirport->scoresAtEta(now(), $direction == 'departure');
                $primaryAirport->setRelation('scores', $primaryScores);

                // To ensure bookmarks works, let's comapre the searchVersion
                $searchVersionWarning = false;
                if (isset($data['searchVersion']) && (int) $data['searchVersion'] != config('app.searchVersion')) {
                    $searchVersionWarning = 'The search form has changed. Edit the search and bookmark again to ensure correct results.';
                }

                return view('search.airports', compact('suggestedAirports', 'primaryAirport', 'direction', 'airportCoordinates', 'suggestedAirport', 'filterByScores', 'sortByScores', 'filterByAircrafts', 'bearingWarning', 'searchVersionWarning'));
            }

        }

        return redirect(route('front'))->withErrors(['airportNotFound' => 'No suitable airport could be found with given criteria', 'bearingWarning' => $bearingWarning])->withInput();
    }

    /**
     * Search for a route
     *
     * @return RedirectResponse
     */
    public function searchRoutes(Request $request)
    {

        $data = request()->validate([
            'departure' => ['required', new AirportExists],
            'arrival' => ['required', new AirportExists],
            'sort' => ['required', 'in:flight,airline,timestamp'],
        ]);

        $departure = Airport::where('icao', $data['departure'])->orWhere('local_code', $data['departure'])->first();
        $arrival = Airport::where('icao', $data['arrival'])->orWhere('local_code', $data['arrival'])->first();

        $routes = Flight::where('airport_dep_id', $departure->id)->where('airport_arr_id', $arrival->id)->whereHas('airline')->with('airline', 'aircrafts')->get();

        if ($routes->count() == 0) {
            return redirect()->route('front.routes')->withErrors(['routeNotFound' => 'No routes found between ' . $departure->icao . ' and ' . $arrival->icao]);
        }

        // Sort the routes based on the selected criteria
        switch ($data['sort']) {
            case 'flight':
                $routes = $routes->sortBy('flight_icao');
                break;
            case 'timestamp':
                $routes = $routes->sortByDesc('last_seen_at');
                break;
        }

        if ($routes->count()) {

            // Create an array with all airports coordinates
            $airportCoordinates = [];
            $airportCoordinates[$departure->icao]['id'] = $departure->id;
            $airportCoordinates[$arrival->icao]['id'] = $arrival->id;
            $airportCoordinates[$departure->icao]['icao'] = $departure->icao;
            $airportCoordinates[$arrival->icao]['icao'] = $arrival->icao;
            $airportCoordinates[$departure->icao]['lat'] = $departure->coordinates->latitude;
            $airportCoordinates[$departure->icao]['lon'] = $departure->coordinates->longitude;
            $airportCoordinates[$arrival->icao]['lat'] = $arrival->coordinates->latitude;
            $airportCoordinates[$arrival->icao]['lon'] = $arrival->coordinates->longitude;
            $airportCoordinates[$departure->icao]['type'] = $departure->type;
            $airportCoordinates[$arrival->icao]['type'] = $arrival->type;

            return view('search.routes', compact('routes', 'departure', 'arrival', 'airportCoordinates'));
        } else {
            return redirect()->route('front.routes')->withErrors(['routeNotFound' => 'No routes found between ' . $departure->icao . ' and ' . $arrival->icao]);
        }

    }

    /**
     * Edit an existing search
     *
     * @return RedirectResponse
     */
    public function searchEdit(Request $request)
    {
        $direction = $request->input('direction');

        if ($direction == 'arrival') {
            return redirect()->route('front.departures')->withInput();
        }

        return redirect()->route('front')->withInput();
    }

    /**
     * Get the relevant whitelist data.
     * Note: This allows a user to get whitelists even if they don't own them.
     */
    private function getWhitelistsFromInput($old)
    {
        return UserList::whereIn('id', $old)->get();
    }

    /**
     * Get destination outputs array
     */
    private function getDestinationInputs()
    {
        return [
            'Anywhere' => 'Anywhere',
            'Domestic' => 'Domestic Only',
            'Continents' => [
                'C-AF' => 'Africa',
                'C-AS' => 'Asia',
                'C-EU' => 'Europe',
                'C-NA' => 'North America',
                'C-SA' => 'South America',
                'C-OC' => 'Oceania',
            ],
            'Countries' => CountryHelper::names(),
            'US States' => [
                ...array_combine(array_map(fn ($key) => 'US-' . $key, array_keys(CountryHelper::US_STATES)), CountryHelper::US_STATES),
            ],
        ];
    }

    /**
     * Filter the destinations to arrays based on continent, country or state
     */
    private function filterDestinations(array $destinations)
    {
        $continents = null;
        $countries = null;
        $states = null;

        // Continents start with 'C-', countries with no prefix and states with 'US-'
        foreach ($destinations as $destination) {
            if ($destination == 'Anywhere') {
                return [
                    'continents' => null,
                    'countries' => null,
                    'states' => null,
                ];
            } elseif ($destination == 'Domestic') {
                return [
                    'continents' => null,
                    'countries' => 'Domestic',
                    'states' => null,
                ];
            } elseif (str_starts_with($destination, 'C-')) {
                $continents[] = substr($destination, 2);
            } elseif (str_starts_with($destination, 'US-')) {
                $states[] = $destination;
            } else {
                $countries[] = $destination;
            }
        }

        return [
            'continents' => $continents,
            'countries' => $countries,
            'states' => $states,
        ];
    }
}
