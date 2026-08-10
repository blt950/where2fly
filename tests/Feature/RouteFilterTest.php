<?php

namespace Tests\Feature;

use App\Models\Aircraft;
use App\Models\Airport;
use App\Models\Flight;
use App\Models\FlightAircraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * filterRoutesAndAirlines matches aircraft through the flight_aircraft pivot —
 * these pin its semantics, incl. the unknown-ICAO edges in both directions.
 */
class RouteFilterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Flight/FlightAircraft are guarded, so fill them explicitly.
     */
    private function seedFlight(string $depIcao, string $arrIcao, string $airlineIcao, array $aircraftIcaos, int $seenCounter = 10): Flight
    {
        $dep = Airport::where('icao', $depIcao)->firstOrFail();
        $arr = Airport::where('icao', $arrIcao)->firstOrFail();

        $flight = new Flight;
        $flight->forceFill([
            'airline_icao' => $airlineIcao,
            'airline_iata' => substr($airlineIcao, 0, 2),
            'flight_number' => (string) fake()->numberBetween(100, 999),
            'flight_icao' => $airlineIcao . $depIcao . $arrIcao,
            'airport_dep_id' => $dep->id,
            'dep_icao' => $depIcao,
            'airport_arr_id' => $arr->id,
            'arr_icao' => $arrIcao,
            'last_aircraft_icao' => $aircraftIcaos[0] ?? '',
            'seen_counter' => $seenCounter,
        ])->save();

        foreach ($aircraftIcaos as $icao) {
            $aircraft = Aircraft::firstOrCreate(['icao' => $icao]);

            (new FlightAircraft)->forceFill([
                'flight_id' => $flight->id,
                'aircraft_id' => $aircraft->id,
            ])->save();
        }

        return $flight;
    }

    /**
     * @return array<int, string>
     */
    private function filter(...$args): array
    {
        return Airport::query()->filterRoutesAndAirlines(...$args)->pluck('icao')->sort()->values()->all();
    }

    // -------------------------------------------------------------------------
    // Aircraft filter
    // -------------------------------------------------------------------------

    public function test_aircraft_filter_returns_only_airports_served_by_that_aircraft(): void
    {
        $this->seedFlight('ENGM', 'EDDF', 'SAS', ['B738']);
        $this->seedFlight('ENGM', 'EGLL', 'BAW', ['A320']);

        $this->assertSame(['EDDF'], $this->filter(null, null, ['B738'], null));
        $this->assertSame(['EGLL'], $this->filter(null, null, ['A320'], null));
        $this->assertSame(['EDDF', 'EGLL'], $this->filter(null, null, ['A320', 'B738'], null));
    }

    public function test_aircraft_filter_matches_any_aircraft_on_a_multi_type_flight(): void
    {
        $this->seedFlight('ENGM', 'EDDF', 'SAS', ['B738', 'A320']);

        $this->assertSame(['EDDF'], $this->filter(null, null, ['A320'], null));
    }

    public function test_aircraft_filter_ignores_rarely_seen_flights(): void
    {
        $this->seedFlight('ENGM', 'EDDF', 'SAS', ['B738'], seenCounter: 3);

        $this->assertSame([], $this->filter(null, null, ['B738'], null));
    }

    public function test_unknown_aircraft_icao_matches_nothing(): void
    {
        $this->seedFlight('ENGM', 'EDDF', 'SAS', ['B738']);

        $this->assertSame([], $this->filter(null, null, ['ZZZZ'], null));
    }

    public function test_aircraft_filter_is_scoped_to_the_departure_airport(): void
    {
        $this->seedFlight('ENGM', 'EDDF', 'SAS', ['B738']);
        $this->seedFlight('ENBR', 'EGLL', 'BAW', ['B738']);

        $this->assertSame(['EDDF'], $this->filter('ENGM', null, ['B738'], null));
    }

    public function test_aircraft_filter_follows_the_departure_flight_direction(): void
    {
        // departureFlights matches the anchor against arr_icao, not dep_icao
        $this->seedFlight('EDDF', 'ENGM', 'SAS', ['B738']);

        $this->assertSame(['EDDF'], $this->filter('ENGM', null, ['B738'], 1, 'departureFlights'));
        $this->assertSame([], $this->filter('ENGM', null, ['B738'], 1));
    }

    // -------------------------------------------------------------------------
    // Airline filter, and the two combined
    // -------------------------------------------------------------------------

    public function test_airline_filter_returns_only_airports_served_by_that_airline(): void
    {
        $this->seedFlight('ENGM', 'EDDF', 'SAS', ['B738']);
        $this->seedFlight('ENGM', 'EGLL', 'BAW', ['B738']);

        $this->assertSame(['EDDF'], $this->filter(null, ['SAS'], null, null));
    }

    public function test_airline_and_aircraft_filters_must_both_match_the_same_flight(): void
    {
        $this->seedFlight('ENGM', 'EDDF', 'SAS', ['B738']);
        $this->seedFlight('ENGM', 'EGLL', 'BAW', ['A320']);

        $this->assertSame(['EDDF'], $this->filter(null, ['SAS'], ['B738'], null));
        $this->assertSame([], $this->filter(null, ['SAS'], ['A320'], null));
    }

    // -------------------------------------------------------------------------
    // destinationWithRoutesOnly
    // -------------------------------------------------------------------------

    public function test_routes_only_keeps_airports_with_matching_routes(): void
    {
        $this->seedFlight('ENGM', 'EDDF', 'SAS', ['B738']);
        $this->seedFlight('ENGM', 'EGLL', 'BAW', ['A320']);

        $this->assertSame(['EDDF'], $this->filter('ENGM', null, ['B738'], 1));
    }

    public function test_routes_only_negative_excludes_airports_with_matching_routes(): void
    {
        $this->seedFlight('ENGM', 'EDDF', 'SAS', ['B738']);

        $withoutB738 = $this->filter('ENGM', null, ['B738'], -1);

        $this->assertNotContains('EDDF', $withoutB738);
        $this->assertContains('EGLL', $withoutB738);
    }

    public function test_routes_only_negative_with_unknown_aircraft_excludes_nothing(): void
    {
        $this->seedFlight('ENGM', 'EDDF', 'SAS', ['B738']);

        $this->assertContains('EDDF', $this->filter('ENGM', null, ['ZZZZ'], -1));
    }

    public function test_routes_only_negative_ignores_rarely_seen_flights(): void
    {
        // Below the threshold the route isn't served, so it survives exclusion
        $this->seedFlight('ENGM', 'EDDF', 'SAS', ['B738'], seenCounter: 3);

        $this->assertContains('EDDF', $this->filter('ENGM', null, ['B738'], -1));
    }

    // -------------------------------------------------------------------------
    // No filters
    // -------------------------------------------------------------------------

    public function test_no_filters_leaves_the_query_untouched(): void
    {
        $this->seedFlight('ENGM', 'EDDF', 'SAS', ['B738']);

        $this->assertSame(Airport::count(), count($this->filter(null, null, null, null)));
    }
}
