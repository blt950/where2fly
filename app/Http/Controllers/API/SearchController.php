<?php

namespace App\Http\Controllers\API;

use App\Helpers\CalculationHelper;
use App\Http\Controllers\Controller;
use App\Models\Airport;
use App\Rules\AirportExists;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request)
    {

        /**
         *  Validate the request and mapping of arguments
         */
        $data = request()->validate([
            'departure' => ['nullable', new AirportExists],
            'arrival' => ['nullable', new AirportExists],
            'continent' => 'required_without:arrivalWhitelist|string',
            'codeletter' => 'required|string',
            'airtimeMin' => 'sometimes|numeric|between:0,24',
            'airtimeMax' => 'sometimes|numeric|between:0,24',
            'scores' => 'sometimes|array',
            'metcondition' => 'sometimes|in:IFR,VFR,ANY',
            'destinationWithRoutesOnly' => 'sometimes|numeric|between:-1,1',
            'destinationRunwayLights' => 'sometimes|numeric|between:-1,1',
            'destinationAirbases' => 'sometimes|numeric|between:-1,1',
            'destinationAirportSize' => 'sometimes|array',
            'elevationMin' => 'sometimes|numeric|between:-2000,18000',
            'elevationMax' => 'sometimes|numeric|between:-2000,18000',
            'rwyLengthMin' => 'sometimes|numeric|between:0,17000',
            'rwyLengthMax' => 'sometimes|numeric|between:0,17000',

            'arrivalWhitelist' => 'sometimes|array',
            'limit' => 'sometimes|integer|between:1,30',
        ]);

        isset($data['departure']) ? $departure = $data['departure'] : $departure = null;
        isset($data['arrival']) ? $arrival = $data['arrival'] : $arrival = null;
        isset($data['continent']) ? $continent = $data['continent'] : $continent = null;
        $codeletter = $data['codeletter'];
        isset($data['airtimeMin']) ? $airtimeMin = $data['airtimeMin'] : $airtimeMin = 0;
        isset($data['airtimeMax']) ? $airtimeMax = $data['airtimeMax'] : $airtimeMax = 24;
        isset($data['scores']) ? $filterByScores = array_map('intval', $data['scores']) : $filterByScores = null;
        isset($data['metcondition']) ? $metcon = $data['metcondition'] : $metcon = null;
        isset($data['destinationRunwayLights']) ? $destinationRunwayLights = (int) $data['destinationRunwayLights'] : $destinationRunwayLights = 0;
        isset($data['destinationAirbases']) ? $destinationAirbases = (int) $data['destinationAirbases'] : $destinationAirbases = 0;
        (isset($data['destinationAirportSize']) && ! empty($data['destinationAirportSize'])) ? $destinationAirportSize = $data['destinationAirportSize'] : $destinationAirportSize = ['small_airport', 'medium_airport', 'large_airport'];
        isset($data['elevationMin']) ? $elevationMin = $data['elevationMin'] : $elevationMin = -2000;
        isset($data['elevationMax']) ? $elevationMax = $data['elevationMax'] : $elevationMax = 18000;
        isset($data['rwyLengthMin']) ? $rwyLengthMin = $data['rwyLengthMin'] : $rwyLengthMin = 0;
        isset($data['rwyLengthMax']) ? $rwyLengthMax = $data['rwyLengthMax'] : $rwyLengthMax = 17000;

        isset($data['arrivalWhitelist']) ? $arrivalWhitelist = $data['arrivalWhitelist'] : $arrivalWhitelist = null;
        isset($data['limit']) ? $resultLimit = $data['limit'] : $resultLimit = 10;

        [$minDistance, $maxDistance] = CalculationHelper::aircraftNmPerHourRange($codeletter, $airtimeMin, $airtimeMax);

        if (($arrival && $departure) || (! $arrival && ! $departure)) {
            // Dont allow this, return error json
            return response()->json([
                'message' => 'You cannot search for both departure and arrival at the same time',
            ], 400);
        }

        /**
         *  Fetch the requested data
         */
        if ($departure) {
            $airport = Airport::where('icao', $departure)->orWhere('local_code', $departure)->get()->first();
        } else {
            $airport = Airport::where('icao', $arrival)->orWhere('local_code', $arrival)->get()->first();
        }

        $airports = collect();
        $airports = Airport::airportOpen()->notIcao($airport->icao)->isAirportSize($destinationAirportSize)
            ->inContinent($continent)->withinDistance($airport, $minDistance, $maxDistance, $airport->icao)
            ->filterRunwayLights($destinationRunwayLights)->filterAirbases($destinationAirbases)->filterByScores($filterByScores)
            ->returnOnlyWhitelistedIcao($arrivalWhitelist)
            ->sortByScores(($filterByScores) ? array_flip($filterByScores) : null)
            ->has('metar')->with('runways', 'scores', 'metar')
            ->get();

        // Shuffle and limit the results to 20
        $airports = $airports->groupBy('score_count')->map(function ($group) {
            return $group->shuffle();
        })->flatten(1)->take(20);

        $suggestedAirports = $airports->filterWithCriteria($airport, $codeletter, $airtimeMin, $airtimeMax, $metcon, $rwyLengthMin, $rwyLengthMax, $elevationMin, $elevationMax);

        /**
         *  Prepare the data for the response
         */

        // Then in your main function
        if ($departure) {
            [$airportData, $arrivalData] = $this->prepareAirportData($airport, $suggestedAirports);
        } else {
            [$airportData, $departureData] = $this->prepareAirportData($airport, $suggestedAirports);
        }

        // Send the response
        return response()->json([
            'message' => 'Success',
            'data' => [
                'departure' => $departure ? $airportData : $departureData,
                'arrivals' => $departure ? $arrivalData : $airportData,
            ],
        ], 200);

    }

    public function prepareAirportData($airport, $suggestedAirports)
    {
        $airportData = [
            'name' => $airport->name,
            'icao' => $airport->icao,
            'iata' => $airport->iata_code ? $airport->iata_code : null,
            'contient' => $airport->continent,
            'country' => $airport->iso_country,
            'region' => $airport->iso_region,
            'metar' => (config('app.env') == 'production') ? $airport->metar->metar : 'TEST-DATA ' . $airport->metar->metar,
            'longestRwyFt' => $airport->longestRunway(),
            'scores' => $airport->scores->pluck('reason'),
        ];

        $suggestedData = collect();
        foreach ($suggestedAirports as $suggestedAirport) {
            $scores = $suggestedAirport->scores->pluck('reason');
            $suggestedData->push([
                'name' => $suggestedAirport->name,
                'icao' => $suggestedAirport->icao,
                'iata' => $suggestedAirport->iata_code ? $suggestedAirport->iata_code : null,
                'contient' => $suggestedAirport->continent,
                'country' => $suggestedAirport->iso_country,
                'region' => $suggestedAirport->iso_region,
                'metar' => (config('app.env') == 'production') ? $suggestedAirport->metar->metar : 'TEST-DATA ' . $suggestedAirport->metar->metar,
                'longestRwyFt' => $suggestedAirport->longestRunway(),
                'scores' => $scores,
                'airtime' => $suggestedAirport->airtime,
                'distanceNm' => $suggestedAirport->distance,
                'isAirforcebase' => $suggestedAirport->w2f_airforcebase,
                'hasAirlineService' => $suggestedAirport->w2f_scheduled_service,
            ]);
        }

        return [$airportData, $suggestedData];
    }
}
