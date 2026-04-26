<?php

namespace Tests\Feature;

use App\Models\Airport;
use App\Models\User;
use App\Models\UserList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    private array $validSearchParams = [
        'icao' => 'ENGM',
        'direction' => 'departure',
        'destinations' => ['Anywhere'],
        'codeletter' => 'JM',
        'airtimeMin' => 0,
        'airtimeMax' => 5,
        'sortByWeather' => 1,
        'sortByATC' => 1,
        'scores' => [
            'METAR_WINDY' => 0,
            'METAR_GUSTS' => 0,
            'METAR_CROSSWIND' => 0,
            'METAR_SIGHT' => 0,
            'METAR_RVR' => 0,
            'METAR_CEILING' => 0,
            'METAR_FOGGY' => 0,
            'METAR_HEAVY_RAIN' => 0,
            'METAR_HEAVY_SNOW' => 0,
            'METAR_THUNDERSTORM' => 0,
            'VATSIM_ATC' => 0,
            'VATSIM_EVENT' => 0,
            'VATSIM_POPULAR' => 0,
        ],
        'metcondition' => 'ANY',
        'destinationWithRoutesOnly' => 0,
        'destinationRunwayLights' => 0,
        'destinationAirbases' => -1,
        'flightDirection' => 0,
        'destinationAirportSize' => ['small_airport', 'medium_airport', 'large_airport'],
        'temperatureMin' => -60,
        'temperatureMax' => 60,
        'elevationMin' => -2000,
        'elevationMax' => 18000,
        'rwyLengthMin' => 0,
        'rwyLengthMax' => 17000,
    ];

    // -------------------------------------------------------------------------
    // Page loading
    // -------------------------------------------------------------------------

    public function test_arrival_search_page_loads(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_departure_search_page_loads(): void
    {
        $response = $this->get('/departures/');

        $response->assertStatus(200);
    }

    public function test_route_search_page_loads(): void
    {
        $response = $this->get('/routes/');

        $response->assertStatus(200);
    }

    // -------------------------------------------------------------------------
    // Search validation
    // -------------------------------------------------------------------------

    public function test_search_requires_direction(): void
    {
        $response = $this->get('/search?' . http_build_query([array_merge($this->validSearchParams, [
            'direction' => null,
        ])]));

        // Missing required fields → redirected back with errors
        $response->assertRedirect();
    }

    public function test_search_fails_with_invalid_codeletter(): void
    {
        $response = $this->get('/search?' . http_build_query(array_merge($this->validSearchParams, [
            'codeletter' => 'INVALID',
        ])));

        $response->assertSessionHasErrors('codeletter');
    }

    public function test_search_fails_with_invalid_metcondition(): void
    {
        $response = $this->get('/search?' . http_build_query(array_merge($this->validSearchParams, [
            'metcondition' => 'INVALID',
        ])));

        $response->assertSessionHasErrors('metcondition');
    }

    // -------------------------------------------------------------------------
    // Search edit
    // -------------------------------------------------------------------------

    public function test_search_edit_redirects_to_arrival_page_for_departure_direction(): void
    {
        $response = $this->post('/search/edit', [
            'direction' => 'departure',
        ]);

        $response->assertRedirect(route('front'));
    }

    public function test_search_edit_redirects_to_departure_page_for_arrival_direction(): void
    {
        $response = $this->post('/search/edit', [
            'direction' => 'arrival',
        ]);

        $response->assertRedirect(route('front.departures'));
    }

    // -------------------------------------------------------------------------
    // Search results
    // -------------------------------------------------------------------------

    public function test_search_fails_with_unrealistic_flight_lenght_and_destinations(): void
    {
        $response = $this->get('/search?' . http_build_query(array_merge($this->validSearchParams, [
            'icao' => 'ENGM',
            'airtimeMin' => '0',
            'airtimeMax' => '2',
            'destinations' => ['C-AS'],
        ])));

        $response->assertSessionHasErrors('airportNotFound');
    }

    public function test_search_doesnt_show_airport_without_metar(): void
    {
        $response = $this->get('/search?' . http_build_query(array_merge($this->validSearchParams, [
            'icao' => 'ENBR',
            'destinations' => ['Domestic'],
            'airtimeMin' => '0',
            'airtimeMax' => '5',
        ])));

        $response->assertOk();
        $response->assertViewHas('suggestedAirports', function ($airports) {
            return $airports->pluck('icao')->doesntContain('ENSO');
        });
    }

    public function test_search_only_shows_domestic_when_searching_for_domestic(): void
    {
        $response = $this->get('/search?' . http_build_query(array_merge($this->validSearchParams, [
            'icao' => 'ENBR',
            'destinations' => ['Domestic'],
            'airtimeMin' => '0',
            'airtimeMax' => '5',
        ])));

        $response->assertOk();
        $response->assertViewHas('suggestedAirports', function ($airports) {
            return $airports->pluck('iso_country')->unique()->count() === 1
                && $airports->first()->iso_country === 'NO';
        });
    }

    public function test_default_search_returns_at_least_3_results(): void
    {
        $response = $this->get('/search?' . http_build_query($this->validSearchParams));

        $response->assertOk();
        $response->assertViewHas('suggestedAirports', function ($airports) {
            return $airports->count() >= 3;
        });
    }

    public function test_whitelist_restricts_results_to_whitelisted_airports(): void
    {
        $user = User::factory()->create();
        $enbr = Airport::where('icao', 'ENBR')->first();
        $list = UserList::create(['name' => 'Test Whitelist', 'user_id' => $user->id, 'public' => true]);
        $list->airports()->attach($enbr);

        $response = $this->get('/search?' . http_build_query(array_merge($this->validSearchParams, [
            'whitelists' => [$list->id],
        ])));

        $response->assertOk();
        $response->assertViewHas('suggestedAirports', function ($airports) {
            return $airports->isNotEmpty()
                && $airports->pluck('icao')->every(fn ($icao) => $icao === 'ENBR');
        });
    }

    public function test_search_airtime_is_within_searched_bounds(): void
    {
        $response = $this->get('/search?' . http_build_query(array_merge($this->validSearchParams, [
            'icao' => 'ENGM',
            'airtimeMin' => '0',
            'airtimeMax' => '2',
        ])));

        $response->assertOk();
        $response->assertViewHas('suggestedAirports', function ($airports) {
            return $airports->isNotEmpty()
                && $airports->every(fn ($a) => $a->airtime >= 0.0 && $a->airtime <= 2.0);
        });
    }

    public function test_search_foggy_score_filter_returns_only_foggy_airports(): void
    {
        $response = $this->get('/search?' . http_build_query(array_merge($this->validSearchParams, [
            'icao' => 'ENGM',
            'scores' => array_merge($this->validSearchParams['scores'], ['METAR_FOGGY' => 1]),
        ])));

        $response->assertOk();
        $response->assertViewHas('suggestedAirports', function ($airports) {
            return $airports->isNotEmpty()
                && $airports->pluck('icao')->every(fn ($icao) => $icao === 'ENTC');
        });
    }

    // -------------------------------------------------------------------------
    // Weather conditions (metcondition)
    // -------------------------------------------------------------------------

    public function test_metcondition_vfr_excludes_ifr_airports(): void
    {
        $response = $this->get('/search?' . http_build_query(array_merge($this->validSearchParams, [
            'metcondition' => 'VFR',
        ])));

        $response->assertOk();
        $response->assertViewHas('suggestedAirports', function ($airports) {
            // ENTC has 1800m visibility (IFR) — must not appear in VFR results
            return $airports->isNotEmpty()
                && $airports->pluck('icao')->doesntContain('ENTC');
        });
    }

    public function test_metcondition_ifr_only_returns_ifr_airports(): void
    {
        $response = $this->get('/search?' . http_build_query(array_merge($this->validSearchParams, [
            'metcondition' => 'IFR',
        ])));

        $response->assertOk();
        $response->assertViewHas('suggestedAirports', function ($airports) {
            // ENTC is the only IFR airport in the seed data within 5h JM range
            return $airports->isNotEmpty()
                && $airports->pluck('icao')->every(fn ($icao) => $icao === 'ENTC');
        });
    }

    // -------------------------------------------------------------------------
    // Score filters — include (=1): each score type assigned to one airport
    // -------------------------------------------------------------------------

    public function test_score_windy_only_shows_windy_airports(): void
    {
        $response = $this->get('/search?' . http_build_query($this->paramsWithScore('METAR_WINDY', 1)));

        $response->assertOk();
        $response->assertViewHas('suggestedAirports', function ($airports) {
            return $airports->isNotEmpty()
                && $airports->pluck('icao')->every(fn ($icao) => $icao === 'ENBR');
        });
    }

    public function test_score_gusts_only_shows_gusty_airports(): void
    {
        $response = $this->get('/search?' . http_build_query($this->paramsWithScore('METAR_GUSTS', 1)));

        $response->assertOk();
        $response->assertViewHas('suggestedAirports', function ($airports) {
            return $airports->isNotEmpty()
                && $airports->pluck('icao')->every(fn ($icao) => $icao === 'ENTO');
        });
    }

    public function test_score_crosswind_only_shows_crosswind_airports(): void
    {
        $response = $this->get('/search?' . http_build_query($this->paramsWithScore('METAR_CROSSWIND', 1)));

        $response->assertOk();
        $response->assertViewHas('suggestedAirports', function ($airports) {
            return $airports->isNotEmpty()
                && $airports->pluck('icao')->every(fn ($icao) => $icao === 'ENSG');
        });
    }

    public function test_score_sight_only_shows_reduced_sight_airports(): void
    {
        $response = $this->get('/search?' . http_build_query($this->paramsWithScore('METAR_SIGHT', 1)));

        $response->assertOk();
        $response->assertViewHas('suggestedAirports', function ($airports) {
            return $airports->isNotEmpty()
                && $airports->pluck('icao')->every(fn ($icao) => $icao === 'ENSD');
        });
    }

    public function test_score_rvr_only_shows_rvr_airports(): void
    {
        $response = $this->get('/search?' . http_build_query($this->paramsWithScore('METAR_RVR', 1)));

        $response->assertOk();
        $response->assertViewHas('suggestedAirports', function ($airports) {
            return $airports->isNotEmpty()
                && $airports->pluck('icao')->every(fn ($icao) => $icao === 'EDDB');
        });
    }

    public function test_score_ceiling_only_shows_low_ceiling_airports(): void
    {
        $response = $this->get('/search?' . http_build_query($this->paramsWithScore('METAR_CEILING', 1)));

        $response->assertOk();
        $response->assertViewHas('suggestedAirports', function ($airports) {
            return $airports->isNotEmpty()
                && $airports->pluck('icao')->every(fn ($icao) => $icao === 'EHAM');
        });
    }

    public function test_score_heavy_rain_only_shows_heavy_rain_airports(): void
    {
        $response = $this->get('/search?' . http_build_query($this->paramsWithScore('METAR_HEAVY_RAIN', 1)));

        $response->assertOk();
        $response->assertViewHas('suggestedAirports', function ($airports) {
            return $airports->isNotEmpty()
                && $airports->pluck('icao')->every(fn ($icao) => $icao === 'EGLL');
        });
    }

    public function test_score_heavy_snow_only_shows_heavy_snow_airports(): void
    {
        $response = $this->get('/search?' . http_build_query($this->paramsWithScore('METAR_HEAVY_SNOW', 1)));

        $response->assertOk();
        $response->assertViewHas('suggestedAirports', function ($airports) {
            return $airports->isNotEmpty()
                && $airports->pluck('icao')->every(fn ($icao) => $icao === 'EDDF');
        });
    }

    public function test_score_thunderstorm_only_shows_thunderstorm_airports(): void
    {
        $response = $this->get('/search?' . http_build_query($this->paramsWithScore('METAR_THUNDERSTORM', 1)));

        $response->assertOk();
        $response->assertViewHas('suggestedAirports', function ($airports) {
            return $airports->isNotEmpty()
                && $airports->pluck('icao')->every(fn ($icao) => $icao === 'EDDS');
        });
    }

    public function test_score_vatsim_atc_only_shows_atc_airports(): void
    {
        $response = $this->get('/search?' . http_build_query($this->paramsWithScore('VATSIM_ATC', 1)));

        $response->assertOk();
        $response->assertViewHas('suggestedAirports', function ($airports) {
            return $airports->isNotEmpty()
                && $airports->pluck('icao')->every(fn ($icao) => $icao === 'LFPG');
        });
    }

    public function test_score_vatsim_event_only_shows_event_airports(): void
    {
        $response = $this->get('/search?' . http_build_query($this->paramsWithScore('VATSIM_EVENT', 1)));

        $response->assertOk();
        $response->assertViewHas('suggestedAirports', function ($airports) {
            return $airports->isNotEmpty()
                && $airports->pluck('icao')->every(fn ($icao) => $icao === 'EDDM');
        });
    }

    public function test_score_vatsim_popular_only_shows_popular_airports(): void
    {
        $response = $this->get('/search?' . http_build_query($this->paramsWithScore('VATSIM_POPULAR', 1)));

        $response->assertOk();
        $response->assertViewHas('suggestedAirports', function ($airports) {
            return $airports->isNotEmpty()
                && $airports->pluck('icao')->every(fn ($icao) => $icao === 'ENTC');
        });
    }

    // -------------------------------------------------------------------------
    // Score filters — exclude (=-1)
    // -------------------------------------------------------------------------

    public function test_score_foggy_exclusion_hides_scored_airports(): void
    {
        $response = $this->get('/search?' . http_build_query($this->paramsWithScore('METAR_FOGGY', -1)));

        $response->assertOk();
        $response->assertViewHas('suggestedAirports', function ($airports) {
            return $airports->isNotEmpty()
                && $airports->pluck('icao')->doesntContain('ENTC');
        });
    }

    public function test_score_vatsim_popular_exclusion_hides_scored_airports(): void
    {
        $response = $this->get('/search?' . http_build_query($this->paramsWithScore('VATSIM_POPULAR', -1)));

        $response->assertOk();
        $response->assertViewHas('suggestedAirports', function ($airports) {
            return $airports->isNotEmpty()
                && $airports->pluck('icao')->doesntContain('ENTC');
        });
    }

    // -------------------------------------------------------------------------
    // Destination filters
    // -------------------------------------------------------------------------

    public function test_destination_small_airports_only_returns_small_airports(): void
    {
        $response = $this->get('/search?' . http_build_query(array_merge($this->validSearchParams, [
            'destinationAirportSize' => ['small_airport'],
        ])));

        $response->assertOk();
        $response->assertViewHas('suggestedAirports', function ($airports) {
            return $airports->isNotEmpty()
                && $airports->pluck('type')->every(fn ($t) => $t === 'small_airport');
        });
    }

    public function test_destination_large_airports_only_excludes_smaller_types(): void
    {
        $response = $this->get('/search?' . http_build_query(array_merge($this->validSearchParams, [
            'destinationAirportSize' => ['large_airport'],
        ])));

        $response->assertOk();
        $response->assertViewHas('suggestedAirports', function ($airports) {
            return $airports->isNotEmpty()
                && $airports->pluck('type')->every(fn ($t) => $t === 'large_airport');
        });
    }

    public function test_destination_country_filter_returns_only_matching_country(): void
    {
        $response = $this->get('/search?' . http_build_query(array_merge($this->validSearchParams, [
            'destinations' => ['DE'],
        ])));

        $response->assertOk();
        $response->assertViewHas('suggestedAirports', function ($airports) {
            return $airports->isNotEmpty()
                && $airports->pluck('iso_country')->every(fn ($c) => $c === 'DE');
        });
    }

    public function test_destination_continent_filter_returns_only_matching_continent(): void
    {
        // From KLAX with C-NA and 0–2h JM only KSFO (~330 nm) is reachable in the seed data
        $response = $this->get('/search?' . http_build_query(array_merge($this->validSearchParams, [
            'icao' => 'KLAX',
            'destinations' => ['C-NA'],
            'airtimeMax' => '2',
        ])));

        $response->assertOk();
        $response->assertViewHas('suggestedAirports', function ($airports) {
            return $airports->isNotEmpty()
                && $airports->pluck('continent')->every(fn ($c) => $c === 'NA');
        });
    }

    public function test_destination_lighted_runways_only_excludes_unlighted_airports(): void
    {
        // ENBO has an unlighted runway and must not appear
        $response = $this->get('/search?' . http_build_query(array_merge($this->validSearchParams, [
            'destinationRunwayLights' => 1,
        ])));

        $response->assertOk();
        $response->assertViewHas('suggestedAirports', function ($airports) {
            return $airports->isNotEmpty()
                && $airports->pluck('icao')->doesntContain('ENBO');
        });
    }

    public function test_destination_unlighted_runways_only_returns_unlighted_airports(): void
    {
        // ENBO is the only seeded airport with an unlighted runway
        $response = $this->get('/search?' . http_build_query(array_merge($this->validSearchParams, [
            'destinationRunwayLights' => -1,
        ])));

        $response->assertOk();
        $response->assertViewHas('suggestedAirports', function ($airports) {
            return $airports->isNotEmpty()
                && $airports->pluck('icao')->every(fn ($icao) => $icao === 'ENBO');
        });
    }

    public function test_destination_airbases_only_returns_airbase_airports(): void
    {
        // ENHF is the only seeded airport with w2f_airforcebase=true
        $response = $this->get('/search?' . http_build_query(array_merge($this->validSearchParams, [
            'destinationAirbases' => 1,
        ])));

        $response->assertOk();
        $response->assertViewHas('suggestedAirports', function ($airports) {
            return $airports->isNotEmpty()
                && $airports->pluck('icao')->every(fn ($icao) => $icao === 'ENHF');
        });
    }

    public function test_destination_exclude_airbases_hides_airbase_airports(): void
    {
        $response = $this->get('/search?' . http_build_query(array_merge($this->validSearchParams, [
            'destinationAirbases' => -1,
        ])));

        $response->assertOk();
        $response->assertViewHas('suggestedAirports', function ($airports) {
            return $airports->isNotEmpty()
                && $airports->pluck('icao')->doesntContain('ENHF');
        });
    }

    public function test_destination_flight_direction_south_excludes_northern_airports(): void
    {
        // From ENGM, flying South: ENTC (69°N, north of ENGM) must not appear
        $response = $this->get('/search?' . http_build_query(array_merge($this->validSearchParams, [
            'flightDirection' => 'S',
        ])));

        $response->assertOk();
        $response->assertViewHas('suggestedAirports', function ($airports) {
            return $airports->isNotEmpty()
                && $airports->pluck('icao')->doesntContain('ENTC');
        });
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function paramsWithScore(string $score, int $value): array
    {
        return array_merge($this->validSearchParams, [
            'scores' => array_merge($this->validSearchParams['scores'], [$score => $value]),
        ]);
    }
}
