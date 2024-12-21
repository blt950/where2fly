<?php

namespace App\Http\Controllers;

use App\Helpers\CalculationHelper;
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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SearchController extends Controller
{
    public static $countries = ['AF' => 'Afghanistan', 'AL' => 'Albania', 'DZ' => 'Algeria', 'AS' => 'American Samoa', 'AD' => 'Andorra', 'AO' => 'Angola', 'AI' => 'Anguilla', 'AQ' => 'Antarctica', 'AG' => 'Antigua And Barbuda', 'AR' => 'Argentina', 'AM' => 'Armenia', 'AW' => 'Aruba', 'AU' => 'Australia', 'AT' => 'Austria', 'AZ' => 'Azerbaijan', 'BS' => 'Bahamas', 'BH' => 'Bahrain', 'BD' => 'Bangladesh', 'BB' => 'Barbados', 'BY' => 'Belarus', 'BE' => 'Belgium', 'BZ' => 'Belize', 'BJ' => 'Benin', 'BM' => 'Bermuda', 'BT' => 'Bhutan', 'BO' => 'Bolivia', 'BA' => 'Bosnia And Herzegovina', 'BW' => 'Botswana', 'BV' => 'Bouvet Island', 'BR' => 'Brazil', 'IO' => 'British Indian Ocean Territory', 'BN' => 'Brunei Darussalam', 'BG' => 'Bulgaria', 'BF' => 'Burkina Faso', 'BI' => 'Burundi', 'KH' => 'Cambodia', 'CM' => 'Cameroon', 'CA' => 'Canada', 'CV' => 'Cape Verde', 'KY' => 'Cayman Islands', 'CF' => 'Central African Republic', 'TD' => 'Chad', 'CL' => 'Chile', 'CN' => 'China', 'CX' => 'Christmas Island', 'CC' => 'Cocos (Keeling) Islands', 'CO' => 'Colombia', 'KM' => 'Comoros', 'CG' => 'Congo', 'CD' => 'Congo, Democratic Republic', 'CK' => 'Cook Islands', 'CR' => 'Costa Rica', 'CI' => 'Cote D\'Ivoire', 'HR' => 'Croatia', 'CU' => 'Cuba', 'CY' => 'Cyprus', 'CZ' => 'Czech Republic', 'DK' => 'Denmark', 'DJ' => 'Djibouti', 'DM' => 'Dominica', 'DO' => 'Dominican Republic', 'EC' => 'Ecuador', 'EG' => 'Egypt', 'SV' => 'El Salvador', 'GQ' => 'Equatorial Guinea', 'ER' => 'Eritrea', 'EE' => 'Estonia', 'ET' => 'Ethiopia', 'FK' => 'Falkland Islands (Malvinas)', 'FO' => 'Faroe Islands', 'FJ' => 'Fiji', 'FI' => 'Finland', 'FR' => 'France', 'GF' => 'French Guiana', 'PF' => 'French Polynesia', 'TF' => 'French Southern Territories', 'GA' => 'Gabon', 'GM' => 'Gambia', 'GE' => 'Georgia', 'DE' => 'Germany', 'GH' => 'Ghana', 'GI' => 'Gibraltar', 'GR' => 'Greece', 'GL' => 'Greenland', 'GD' => 'Grenada', 'GP' => 'Guadeloupe', 'GU' => 'Guam', 'GT' => 'Guatemala', 'GG' => 'Guernsey', 'GN' => 'Guinea', 'GW' => 'Guinea-Bissau', 'GY' => 'Guyana', 'HT' => 'Haiti', 'HM' => 'Heard Island & Mcdonald Islands', 'VA' => 'Holy See (Vatican City State)', 'HN' => 'Honduras', 'HK' => 'Hong Kong', 'HU' => 'Hungary', 'IS' => 'Iceland', 'IN' => 'India', 'ID' => 'Indonesia', 'IR' => 'Iran, Islamic Republic Of', 'IQ' => 'Iraq', 'IE' => 'Ireland', 'IM' => 'Isle Of Man', 'IL' => 'Israel', 'IT' => 'Italy', 'JM' => 'Jamaica', 'JP' => 'Japan', 'JE' => 'Jersey', 'JO' => 'Jordan', 'KZ' => 'Kazakhstan', 'KE' => 'Kenya', 'KI' => 'Kiribati', 'KR' => 'Korea', 'KW' => 'Kuwait', 'KG' => 'Kyrgyzstan', 'LA' => 'Lao People\'s Democratic Republic', 'LV' => 'Latvia', 'LB' => 'Lebanon', 'LS' => 'Lesotho', 'LR' => 'Liberia', 'LY' => 'Libyan Arab Jamahiriya', 'LI' => 'Liechtenstein', 'LT' => 'Lithuania', 'LU' => 'Luxembourg', 'MO' => 'Macao', 'MK' => 'Macedonia', 'MG' => 'Madagascar', 'MW' => 'Malawi', 'MY' => 'Malaysia', 'MV' => 'Maldives', 'ML' => 'Mali', 'MT' => 'Malta', 'MH' => 'Marshall Islands', 'MQ' => 'Martinique', 'MR' => 'Mauritania', 'MU' => 'Mauritius', 'YT' => 'Mayotte', 'MX' => 'Mexico', 'FM' => 'Micronesia, Federated States Of', 'MD' => 'Moldova', 'MC' => 'Monaco', 'MN' => 'Mongolia', 'ME' => 'Montenegro', 'MS' => 'Montserrat', 'MA' => 'Morocco', 'MZ' => 'Mozambique', 'MM' => 'Myanmar', 'NA' => 'Namibia', 'NR' => 'Nauru', 'NP' => 'Nepal', 'NL' => 'Netherlands', 'AN' => 'Netherlands Antilles', 'NC' => 'New Caledonia', 'NZ' => 'New Zealand', 'NI' => 'Nicaragua', 'NE' => 'Niger', 'NG' => 'Nigeria', 'NU' => 'Niue', 'NF' => 'Norfolk Island', 'MP' => 'Northern Mariana Islands', 'NO' => 'Norway', 'OM' => 'Oman', 'PK' => 'Pakistan', 'PW' => 'Palau', 'PS' => 'Palestinian Territory, Occupied', 'PA' => 'Panama', 'PG' => 'Papua New Guinea', 'PY' => 'Paraguay', 'PE' => 'Peru', 'PH' => 'Philippines', 'PN' => 'Pitcairn', 'PL' => 'Poland', 'PT' => 'Portugal', 'PR' => 'Puerto Rico', 'QA' => 'Qatar', 'RE' => 'Reunion', 'RO' => 'Romania', 'RU' => 'Russian Federation', 'RW' => 'Rwanda', 'BL' => 'Saint Barthelemy', 'SH' => 'Saint Helena', 'KN' => 'Saint Kitts And Nevis', 'LC' => 'Saint Lucia', 'MF' => 'Saint Martin', 'PM' => 'Saint Pierre And Miquelon', 'VC' => 'Saint Vincent And Grenadines', 'WS' => 'Samoa', 'SM' => 'San Marino', 'ST' => 'Sao Tome And Principe', 'SA' => 'Saudi Arabia', 'SN' => 'Senegal', 'RS' => 'Serbia', 'SC' => 'Seychelles', 'SL' => 'Sierra Leone', 'SG' => 'Singapore', 'SK' => 'Slovakia', 'SI' => 'Slovenia', 'SB' => 'Solomon Islands', 'SO' => 'Somalia', 'ZA' => 'South Africa', 'GS' => 'South Georgia And Sandwich Isl.', 'ES' => 'Spain', 'LK' => 'Sri Lanka', 'SD' => 'Sudan', 'SR' => 'Suriname', 'SJ' => 'Svalbard And Jan Mayen', 'SZ' => 'Swaziland', 'SE' => 'Sweden', 'CH' => 'Switzerland', 'SY' => 'Syrian Arab Republic', 'TW' => 'Taiwan', 'TJ' => 'Tajikistan', 'TZ' => 'Tanzania', 'TH' => 'Thailand', 'TL' => 'Timor-Leste', 'TG' => 'Togo', 'TK' => 'Tokelau', 'TO' => 'Tonga', 'TT' => 'Trinidad And Tobago', 'TN' => 'Tunisia', 'TR' => 'Turkey', 'TM' => 'Turkmenistan', 'TC' => 'Turks And Caicos Islands', 'TV' => 'Tuvalu', 'UG' => 'Uganda', 'UA' => 'Ukraine', 'AE' => 'United Arab Emirates', 'GB' => 'United Kingdom', 'US' => 'United States', 'UM' => 'United States Outlying Islands', 'UY' => 'Uruguay', 'UZ' => 'Uzbekistan', 'VU' => 'Vanuatu', 'VE' => 'Venezuela', 'VN' => 'Viet Nam', 'VG' => 'Virgin Islands, British', 'VI' => 'Virgin Islands, U.S.', 'WF' => 'Wallis And Futuna', 'EH' => 'Western Sahara', 'XK' => 'Kosovo', 'YE' => 'Yemen', 'ZM' => 'Zambia', 'ZW' => 'Zimbabwe'];

    public static $usStates = ['AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas', 'CA' => 'California', 'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware', 'FL' => 'Florida', 'GA' => 'Georgia', 'HI' => 'Hawaii', 'ID' => 'Idaho', 'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa', 'KS' => 'Kansas', 'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MD' => 'Maryland', 'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi', 'MO' => 'Missouri', 'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada', 'NH' => 'New Hampshire', 'NJ' => 'New Jersey', 'NM' => 'New Mexico', 'NY' => 'New York', 'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio', 'OK' => 'Oklahoma', 'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island', 'SC' => 'South Carolina', 'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah', 'VT' => 'Vermont', 'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia', 'WI' => 'Wisconsin', 'WY' => 'Wyoming'];

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function indexArrivalSearch()
    {
        $airlines = Airline::where('has_flights', true)->orderBy('name')->get();
        $aircrafts = Aircraft::all()->pluck('icao')->sort();
        $prefilledIcao = request()->input('icao');
        $destinationInputs = $this->getDestinationInputs();

        if (Auth::check()) {
            $lists = UserList::where('user_id', Auth::id())->orWhere('public', true)->get();
        } else {
            $lists = UserList::where('public', true)->get();
        }

        return view('front.arrivals', compact('airlines', 'aircrafts', 'prefilledIcao', 'lists', 'destinationInputs'));
    }

    /**
     * Display a listing of the resource.
     */
    public function indexDepartureSearch()
    {
        $airlines = Airline::where('has_flights', true)->orderBy('name')->get();
        $aircrafts = Aircraft::all()->pluck('icao')->sort();
        $prefilledIcao = request()->input('icao');
        $destinationInputs = $this->getDestinationInputs();

        if (Auth::check()) {
            $lists = UserList::where('user_id', Auth::id())->orWhere('public', true)->get();
        } else {
            $lists = UserList::where('public', true)->get();
        }

        return view('front.departures', compact('airlines', 'aircrafts', 'prefilledIcao', 'lists', 'destinationInputs'));
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
     * @return \Illuminate\Http\RedirectResponse
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
            'codeletter' => ['required', 'string', 'in:A,B,C,D,E,F'],
            'airtimeMin' => ['required', 'numeric', 'between:0,12'],
            'airtimeMax' => ['required', 'numeric', 'between:0,12'],
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
            'searchVersion' => ['sometimes', 'numeric'],
        ]);

        if ($validator->fails()) {
            if (isset($validator->getData()['direction']) && $validator->getData()['direction'] == 'arrival') {
                return redirect(route('front.departures'))->withErrors($validator)->withInput();
            }

            return redirect(route('front'))->withErrors($validator)->withInput();
        }

        $data = $validator->validated();

        $direction = $data['direction'];
        $destinations = isset($data['destinations']) ? $this->filterDestinations($data['destinations']) : $this->filterDestinations(['Anywhere']);
        $codeletter = $data['codeletter'];
        $airtimeMin = (int) $data['airtimeMin'];
        $airtimeMax = (int) $data['airtimeMax'];
        if ($airtimeMax == 12) {
            $airtimeMax = 24;
        } // If airtime is 12+ hours, bump it

        // Create a filter array based on input
        $sortByScores = [];
        isset($data['sortByWeather']) ? $sortByScores = array_merge($sortByScores, ScoreController::getWeatherTypes()) : null;
        isset($data['sortByATC']) ? $sortByScores = array_merge($sortByScores, ScoreController::getVatsimTypes()) : null;

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

        isset($data['airlines']) ? $filterByAirlines = $data['airlines'] : $filterByAirlines = null;
        isset($data['aircrafts']) ? $filterByAircrafts = $data['aircrafts'] : $filterByAircrafts = null;

        [$minDistance, $maxDistance] = CalculationHelper::aircraftNmPerHourRange($codeletter, $airtimeMin, $airtimeMax);

        /**
         *  Fetch the requested data
         */

        // Lets find an result with the given criteria. Give it a few attempts before we give up.
        $maxAttempts = 20;
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {

            // Use the supplied departure or select a random airport
            $suggestedAirport = false;
            if (isset($data['icao'])) {
                $primaryAirport = Airport::where('icao', $data['icao'])->orWhere('local_code', $data['icao'])->get()->first();
            } else {
                // Select primary airport based on the criteria
                $primaryAirport = Airport::airportOpen()->isAirportSize($destinationAirportSize)
                    ->filterRunwayLengths($rwyLengthMin, $rwyLengthMax, $codeletter)->filterRunwayLights($destinationRunwayLights)
                    ->filterAirbases($destinationAirbases)->filterByScores($filterByScores)->filterRoutesAndAirlines(null, $filterByAirlines, $filterByAircrafts, $destinationWithRoutesOnly)
                    ->returnOnlyWhitelistedIcao($whitelist)
                    ->has('metar')->with('runways', 'scores', 'metar')
                    ->shuffleAndSort()
                    ->limit(10)
                    ->get();

                if (! $primaryAirport || ! $primaryAirport->count()) {
                    return back()->withErrors(['airportNotFound' => 'No suitable airport combination could be found with given criteria'])->withInput();
                }

                $primaryAirport = $primaryAirport->random();
                $suggestedAirport = true;
            }

            // Get airports according to filter
            $airports = collect();
            $airports = Airport::airportOpen()->notIcao($primaryAirport->icao)->isAirportSize($destinationAirportSize)
                ->inContinent($destinations)->inCountry($destinations, $primaryAirport->iso_country)->inState($destinations)
                ->withinDistance($primaryAirport, $minDistance, $maxDistance, $primaryAirport->icao)->withinBearing($primaryAirport, $flightDirection, $minDistance, $maxDistance)
                ->filterRunwayLengths($rwyLengthMin, $rwyLengthMax, $codeletter)->filterRunwayLights($destinationRunwayLights)
                ->filterAirbases($destinationAirbases)->filterByScores($filterByScores)->filterRoutesAndAirlines($primaryAirport->icao, $filterByAirlines, $filterByAircrafts, $destinationWithRoutesOnly)
                ->returnOnlyWhitelistedIcao($whitelist)
                ->sortByScores($sortByScores)
                ->has('metar')->with('runways', 'scores', 'metar')
                ->shuffleAndSort()
                ->limit(20)
                ->get();

            // Filter the eligible airports
            $suggestedAirports = $airports->filterWithCriteria($primaryAirport, $codeletter, $airtimeMin, $airtimeMax, $metcon, $temperatureMin, $temperatureMax, $rwyLengthMin, $rwyLengthMax, $elevationMin, $elevationMax);

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

                // To ensure bookmarks works, let's comapre the searchVersion
                $searchVersionWarning = false;
                if (isset($data['searchVersion']) && (int) $data['searchVersion'] != 1) {
                    $searchVersionWarning = 'The search form has changed. Please update your bookmarks to ensure correct results.';
                }

                return view('search.airports', compact('suggestedAirports', 'primaryAirport', 'direction', 'airportCoordinates', 'suggestedAirport', 'filterByScores', 'sortByScores', 'filterByAircrafts', 'bearingWarning', 'searchVersionWarning'));
            }

        }

        if ($direction == 'departure') {
            return redirect(route('front'))->withErrors(['airportNotFound' => 'No suitable arrival airport could be found with given criteria', 'bearingWarning' => $bearingWarning])->withInput();
        } else {
            return redirect(route('front'))->withErrors(['airportNotFound' => 'No suitable arrival airport could be found with given criteria', 'bearingWarning' => $bearingWarning])->withInput();
        }
    }

    /**
     * Search for a route
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function searchRoutes(Request $request)
    {

        $data = request()->validate([
            'departure' => ['required', new AirportExists],
            'arrival' => ['required', new AirportExists],
            'sort' => ['required', 'in:flight,airline,timestamp'],
        ]);

        $departure = Airport::where('icao', $data['departure'])->orWhere('local_code', $data['departure'])->get()->first();
        $arrival = Airport::where('icao', $data['arrival'])->orWhere('local_code', $data['arrival'])->get()->first();

        $routes = Flight::where('airport_dep_id', $departure->id)->where('airport_arr_id', $arrival->id)->whereHas('airline')->with('airline', 'aircrafts')->get();

        if ($routes->count() == 0) {
            return back()->withErrors(['routeNotFound' => 'No routes found between ' . $departure->icao . ' and ' . $arrival->icao]);
        }

        // Strip the stars from IATA codes for the logos to display correctly
        $routes = $routes->map(function ($route) {
            $route->airline->iata_code = str_replace('*', '', $route->airline->iata_code);

            return $route;
        });

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
            return back()->withErrors(['routeNotFound' => 'No routes found between ' . $departure->icao . ' and ' . $arrival->icao]);
        }

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
            ],
            'Countries' => [
                ...$this::$countries,
            ],
            'US States' => [
                ...array_combine(array_map(fn ($key) => 'US-' . $key, array_keys($this::$usStates)), $this::$usStates),
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
            } elseif (strpos($destination, 'C-') === 0) {
                $continents[] = substr($destination, 2);
            } elseif (strpos($destination, 'US-') === 0) {
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
