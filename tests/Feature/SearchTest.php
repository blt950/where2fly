<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Airline;
use App\Models\Airport;
use App\Models\Flight;
use App\Models\User;
use App\Models\UserList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
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
        'distanceMin' => 0,
        'distanceMax' => 6000,
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
        $response = $this->get('/search?' . http_build_query(array_merge($this->validSearchParams, [
            'direction' => null,
        ])));

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

    public function test_search_fails_with_non_array_scores(): void
    {
        $response = $this->get('/search?' . http_build_query(array_merge($this->validSearchParams, [
            'scores' => 'foo',
        ])));

        $response->assertSessionHasErrors('scores');
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

    public function test_edit_filters_fills_the_form_back_in(): void
    {
        $query = ['icao' => 'ENGM', 'direction' => 'arrival', 'scores' => ['METAR_WINDY' => '1'], 'destinations' => ['C-EU'], 'sortByWeather' => '0'];

        $this->post('/search/edit', $query)->assertRedirect(route('front.departures'));

        $this->get(route('front.departures'))->assertInertia(fn (Assert $page) => $page->component('SearchForm')
            ->where('anchor', 'arrival')
            ->where('old.icao', 'ENGM')
            ->where('old.scores.METAR_WINDY', '1')
            ->where('old.destinations', ['C-EU'])
            ->where('old.sortByWeather', '0'));
    }

    // -------------------------------------------------------------------------
    // Search forms
    // -------------------------------------------------------------------------

    public function test_arrival_form_asks_for_the_departure(): void
    {
        $this->get('/?icao=ENGM')->assertInertia(fn (Assert $page) => $page->component('SearchForm')
            ->where('anchor', 'departure')
            ->where('area', 'destination')
            ->where('suggestionSide', 'arrival')
            ->where('prefilledIcao', 'ENGM')
            ->where('searchVersion', config('app.searchVersion'))
            ->has('destinationInputs.Continents.C-EU')
            ->has('aircraftTypes.JM.description'));
    }

    public function test_departure_form_asks_for_the_arrival(): void
    {
        $this->get('/departures/')->assertInertia(fn (Assert $page) => $page->component('SearchForm')
            ->where('anchor', 'arrival')
            ->where('area', 'origin')
            ->where('suggestionSide', 'departure'));
    }

    public function test_whitelists_offer_public_lists_and_the_users_own(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        UserList::create(['name' => 'Public', 'user_id' => $other->id, 'public' => true, 'color' => '#fff']);
        UserList::create(['name' => 'Mine', 'user_id' => $user->id, 'public' => false, 'color' => '#fff']);
        UserList::create(['name' => 'Private', 'user_id' => $other->id, 'public' => false, 'color' => '#fff']);

        $this->actingAs($user)->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('lists', fn ($lists) => collect($lists)->pluck('name')->sort()->values()->all() === ['Mine', 'Public']));
    }

    public function test_route_form_gets_its_input_back_after_a_failed_search(): void
    {
        $this->get('/search/routes?departure=ENGM&arrival=EDDF&sort=timestamp')->assertRedirect(route('front.routes'));

        $this->get(route('front.routes'))->assertInertia(fn (Assert $page) => $page->component('RouteSearchForm')
            ->where('old.departure', 'ENGM')
            ->where('old.sort', 'timestamp')
            ->has('errors.routeNotFound'));
    }

    // -------------------------------------------------------------------------
    // Search results
    // -------------------------------------------------------------------------

    public function test_search_fails_with_unrealistic_flight_length_and_destinations(): void
    {
        $response = $this->get('/search?' . http_build_query(array_merge($this->validSearchParams, [
            'icao' => 'ENGM',
            'airtimeMin' => '0',
            'airtimeMax' => '2',
            'destinations' => ['C-AS'],
        ])));

        $response->assertSessionHasErrors('airportNotFound');
    }

    public function test_unchecked_sort_boxes_stay_unchecked_after_a_failed_search(): void
    {
        $unchecked = array_diff_key($this->validSearchParams, array_flip(['sortByWeather', 'sortByATC']));

        $this->get('/search?' . http_build_query(array_merge($unchecked, ['airtimeMax' => '2', 'destinations' => ['C-AS']])))
            ->assertSessionHasErrors('airportNotFound')
            ->assertSessionHasInput('sortByWeather', '0')
            ->assertSessionHasInput('sortByATC', '0');

        $this->get('/search?' . http_build_query(array_merge($unchecked, ['codeletter' => 'INVALID'])))
            ->assertSessionHasErrors('codeletter')
            ->assertSessionHasInput('sortByWeather', '0');
    }

    public function test_sort_flags_of_zero_dont_sort(): void
    {
        // Results are shuffled within score buckets, so the ranking pivot in the SQL is the signal
        $sorts = function (array $flags) {
            $sql = [];
            DB::listen(function ($query) use (&$sql) {
                $sql[] = $query->sql;
            });
            $this->get('/search?' . http_build_query(array_merge($this->validSearchParams, $flags)))->assertOk();

            return collect($sql)->contains(fn ($q) => str_contains($q, 'as score_count'));
        };

        $this->assertFalse($sorts(['sortByWeather' => '0', 'sortByATC' => '0']));
        $this->assertTrue($sorts(['sortByWeather' => '1', 'sortByATC' => '0']));
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
        $this->assertSuggestedAirports($response, function ($airports) {
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
        $this->assertSuggestedAirports($response, function ($airports) {
            return $airports->pluck('iso_country')->unique()->count() === 1
                && $airports->first()->iso_country === 'NO';
        });
    }

    public function test_default_search_returns_at_least_3_results(): void
    {
        $response = $this->get('/search?' . http_build_query($this->validSearchParams));

        $response->assertOk();
        $this->assertSuggestedAirports($response, function ($airports) {
            return $airports->count() >= 3;
        });
    }

    public function test_search_returns_at_most_20_suggestions_ordered_by_score_count(): void
    {
        $response = $this->get('/search?' . http_build_query($this->validSearchParams));

        $response->assertOk();
        $this->assertSuggestedAirports($response, function ($airports) {
            $counts = $airports->pluck('score_count');

            return $airports->count() <= 20
                && $counts->values()->all() === $counts->sortDesc()->values()->all();
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
        $this->assertSuggestedAirports($response, function ($airports) {
            return $airports->isNotEmpty()
                && $airports->pluck('icao')->every(fn ($icao) => $icao === 'ENBR');
        });
    }

    public function test_arrival_whitelist_restricts_suggestions_when_searching_arrivals(): void
    {
        $list = $this->createWhitelist('ENBR');

        $response = $this->get('/search?' . http_build_query(array_merge($this->validSearchParams, [
            'arrivalWhitelists' => [$list->id],
        ])));

        $response->assertOk();
        $this->assertSuggestedAirports($response, function ($airports) {
            return $airports->isNotEmpty()
                && $airports->pluck('icao')->every(fn ($icao) => $icao === 'ENBR');
        });
    }

    public function test_departure_whitelist_restricts_suggestions_when_searching_departures(): void
    {
        $list = $this->createWhitelist('ENBR');

        $response = $this->get('/search?' . http_build_query(array_merge($this->validSearchParams, [
            'direction' => 'arrival',
            'departureWhitelists' => [$list->id],
        ])));

        $response->assertOk();
        $this->assertSuggestedAirports($response, function ($airports) {
            return $airports->isNotEmpty()
                && $airports->pluck('icao')->every(fn ($icao) => $icao === 'ENBR');
        });
    }

    public function test_departure_whitelist_restricts_the_random_anchor(): void
    {
        $list = $this->createWhitelist('ENGM');

        $params = $this->validSearchParams;
        unset($params['icao']);

        $response = $this->get('/search?' . http_build_query(array_merge($params, [
            'departureWhitelists' => [$list->id],
        ])));

        $response->assertOk();
        $this->assertPrimaryAirport($response, fn ($airport) => $airport->icao === 'ENGM');
    }

    public function test_departure_whitelist_is_ignored_when_a_departure_icao_is_given(): void
    {
        $list = $this->createWhitelist('ENBR');

        $response = $this->get('/search?' . http_build_query(array_merge($this->validSearchParams, [
            'icao' => 'ENGM',
            'departureWhitelists' => [$list->id],
        ])));

        $response->assertOk();
        $this->assertPrimaryAirport($response, fn ($airport) => $airport->icao === 'ENGM');
        $this->assertSuggestedAirports($response, fn ($airports) => $airports->isNotEmpty());
    }

    public function test_both_whitelists_apply_to_their_own_side(): void
    {
        $anchorList = $this->createWhitelist('ENGM');
        $suggestionList = $this->createWhitelist('ENBR');

        $params = $this->validSearchParams;
        unset($params['icao']);

        $response = $this->get('/search?' . http_build_query(array_merge($params, [
            'departureWhitelists' => [$anchorList->id],
            'arrivalWhitelists' => [$suggestionList->id],
        ])));

        $response->assertOk();
        $this->assertPrimaryAirport($response, fn ($airport) => $airport->icao === 'ENGM');
        $this->assertSuggestedAirports($response, function ($airports) {
            return $airports->isNotEmpty()
                && $airports->pluck('icao')->every(fn ($icao) => $icao === 'ENBR');
        });
    }

    private function createWhitelist(string $icao): UserList
    {
        $user = User::factory()->create();
        $list = UserList::create(['name' => $icao . ' Whitelist', 'user_id' => $user->id, 'public' => true]);
        $list->airports()->attach(Airport::where('icao', $icao)->first());

        return $list;
    }

    public function test_search_airtime_is_within_searched_bounds(): void
    {
        $response = $this->get('/search?' . http_build_query(array_merge($this->validSearchParams, [
            'icao' => 'ENGM',
            'airtimeMin' => '0',
            'airtimeMax' => '2',
        ])));

        $response->assertOk();
        $this->assertSuggestedAirports($response, function ($airports) {
            return $airports->isNotEmpty()
                && $airports->every(fn ($a) => $a->airtime >= 0.0 && $a->airtime <= 2.0);
        });
    }

    public function test_search_distance_is_within_searched_bounds(): void
    {
        $response = $this->get('/search?' . http_build_query(array_merge($this->validSearchParams, [
            'icao' => 'ENGM',
            'distanceMin' => '300',
            'distanceMax' => '500',
        ])));

        $response->assertOk();
        $this->assertSuggestedAirports($response, function ($airports) {
            return $airports->isNotEmpty()
                && $airports->every(fn ($a) => $a->distance >= 300 && $a->distance <= 500);
        });
    }

    public function test_search_fails_with_out_of_range_distance(): void
    {
        $response = $this->get('/search?' . http_build_query(array_merge($this->validSearchParams, [
            'distanceMax' => '9000',
        ])));

        $response->assertSessionHasErrors('distanceMax');
    }

    // -------------------------------------------------------------------------
    // Random anchor (no ICAO given)
    // -------------------------------------------------------------------------

    public function test_random_search_returns_results(): void
    {
        $params = $this->validSearchParams;
        unset($params['icao']);

        $response = $this->get('/search?' . http_build_query($params));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page->component('SearchResults')->where('suggestedAirport', true));
        $this->assertSuggestedAirports($response, fn ($airports) => $airports->isNotEmpty());
    }

    public function test_random_search_with_event_filter_reaches_the_event_airport(): void
    {
        $params = $this->validSearchParams;
        unset($params['icao']);
        $params['scores'] = array_merge($params['scores'], ['VATSIM_EVENT' => 1]);

        $response = $this->get('/search?' . http_build_query($params));

        // EDDM holds the only seeded event: the anchor is drawn within flying
        // range of it and must not be required to carry the event itself
        $response->assertOk();
        $this->assertPrimaryAirport($response, fn ($airport) => $airport->icao !== 'EDDM');
        $this->assertSuggestedAirports($response, function ($airports) {
            return $airports->isNotEmpty()
                && $airports->pluck('icao')->every(fn ($icao) => $icao === 'EDDM');
        });
    }

    public function test_random_search_with_score_exclusion_returns_results(): void
    {
        $params = $this->validSearchParams;
        unset($params['icao']);
        $params['scores'] = array_merge($params['scores'], ['VATSIM_EVENT' => -1]);

        $response = $this->get('/search?' . http_build_query($params));

        $response->assertOk();
        $this->assertSuggestedAirports($response, function ($airports) {
            return $airports->isNotEmpty()
                && $airports->pluck('icao')->doesntContain('EDDM');
        });
    }

    public function test_search_foggy_score_filter_returns_only_foggy_airports(): void
    {
        $response = $this->get('/search?' . http_build_query(array_merge($this->validSearchParams, [
            'icao' => 'ENGM',
            'scores' => array_merge($this->validSearchParams['scores'], ['METAR_FOGGY' => 1]),
        ])));

        $response->assertOk();
        $this->assertSuggestedAirports($response, function ($airports) {
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
        $this->assertSuggestedAirports($response, function ($airports) {
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
        $this->assertSuggestedAirports($response, function ($airports) {
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
        $this->assertSuggestedAirports($response, function ($airports) {
            return $airports->isNotEmpty()
                && $airports->pluck('icao')->every(fn ($icao) => $icao === 'ENBR');
        });
    }

    public function test_score_gusts_only_shows_gusty_airports(): void
    {
        $response = $this->get('/search?' . http_build_query($this->paramsWithScore('METAR_GUSTS', 1)));

        $response->assertOk();
        $this->assertSuggestedAirports($response, function ($airports) {
            return $airports->isNotEmpty()
                && $airports->pluck('icao')->every(fn ($icao) => $icao === 'ENTO');
        });
    }

    public function test_score_crosswind_only_shows_crosswind_airports(): void
    {
        $response = $this->get('/search?' . http_build_query($this->paramsWithScore('METAR_CROSSWIND', 1)));

        $response->assertOk();
        $this->assertSuggestedAirports($response, function ($airports) {
            return $airports->isNotEmpty()
                && $airports->pluck('icao')->every(fn ($icao) => $icao === 'ENSG');
        });
    }

    public function test_score_sight_only_shows_reduced_sight_airports(): void
    {
        $response = $this->get('/search?' . http_build_query($this->paramsWithScore('METAR_SIGHT', 1)));

        $response->assertOk();
        $this->assertSuggestedAirports($response, function ($airports) {
            return $airports->isNotEmpty()
                && $airports->pluck('icao')->every(fn ($icao) => $icao === 'ENSD');
        });
    }

    public function test_score_rvr_only_shows_rvr_airports(): void
    {
        $response = $this->get('/search?' . http_build_query($this->paramsWithScore('METAR_RVR', 1)));

        $response->assertOk();
        $this->assertSuggestedAirports($response, function ($airports) {
            return $airports->isNotEmpty()
                && $airports->pluck('icao')->every(fn ($icao) => $icao === 'EDDB');
        });
    }

    public function test_score_ceiling_only_shows_low_ceiling_airports(): void
    {
        $response = $this->get('/search?' . http_build_query($this->paramsWithScore('METAR_CEILING', 1)));

        $response->assertOk();
        $this->assertSuggestedAirports($response, function ($airports) {
            return $airports->isNotEmpty()
                && $airports->pluck('icao')->every(fn ($icao) => $icao === 'EHAM');
        });
    }

    public function test_score_heavy_rain_only_shows_heavy_rain_airports(): void
    {
        $response = $this->get('/search?' . http_build_query($this->paramsWithScore('METAR_HEAVY_RAIN', 1)));

        $response->assertOk();
        $this->assertSuggestedAirports($response, function ($airports) {
            return $airports->isNotEmpty()
                && $airports->pluck('icao')->every(fn ($icao) => $icao === 'EGLL');
        });
    }

    public function test_score_heavy_snow_only_shows_heavy_snow_airports(): void
    {
        $response = $this->get('/search?' . http_build_query($this->paramsWithScore('METAR_HEAVY_SNOW', 1)));

        $response->assertOk();
        $this->assertSuggestedAirports($response, function ($airports) {
            return $airports->isNotEmpty()
                && $airports->pluck('icao')->every(fn ($icao) => $icao === 'EDDF');
        });
    }

    public function test_score_thunderstorm_only_shows_thunderstorm_airports(): void
    {
        $response = $this->get('/search?' . http_build_query($this->paramsWithScore('METAR_THUNDERSTORM', 1)));

        $response->assertOk();
        $this->assertSuggestedAirports($response, function ($airports) {
            return $airports->isNotEmpty()
                && $airports->pluck('icao')->every(fn ($icao) => $icao === 'EDDS');
        });
    }

    public function test_score_vatsim_atc_only_shows_atc_airports(): void
    {
        $response = $this->get('/search?' . http_build_query($this->paramsWithScore('VATSIM_ATC', 1)));

        $response->assertOk();
        $this->assertSuggestedAirports($response, function ($airports) {
            return $airports->isNotEmpty()
                && $airports->pluck('icao')->every(fn ($icao) => $icao === 'LFPG');
        });
    }

    public function test_score_vatsim_event_only_shows_event_airports(): void
    {
        $response = $this->get('/search?' . http_build_query($this->paramsWithScore('VATSIM_EVENT', 1)));

        $response->assertOk();
        $this->assertSuggestedAirports($response, function ($airports) {
            return $airports->isNotEmpty()
                && $airports->pluck('icao')->every(fn ($icao) => $icao === 'EDDM');
        });
    }

    public function test_score_vatsim_popular_only_shows_popular_airports(): void
    {
        $response = $this->get('/search?' . http_build_query($this->paramsWithScore('VATSIM_POPULAR', 1)));

        $response->assertOk();
        $this->assertSuggestedAirports($response, function ($airports) {
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
        $this->assertSuggestedAirports($response, function ($airports) {
            return $airports->isNotEmpty()
                && $airports->pluck('icao')->doesntContain('ENTC');
        });
    }

    public function test_score_vatsim_popular_exclusion_hides_scored_airports(): void
    {
        $response = $this->get('/search?' . http_build_query($this->paramsWithScore('VATSIM_POPULAR', -1)));

        $response->assertOk();
        $this->assertSuggestedAirports($response, function ($airports) {
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
        $this->assertSuggestedAirports($response, function ($airports) {
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
        $this->assertSuggestedAirports($response, function ($airports) {
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
        $this->assertSuggestedAirports($response, function ($airports) {
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
        $this->assertSuggestedAirports($response, function ($airports) {
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
        $this->assertSuggestedAirports($response, function ($airports) {
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
        $this->assertSuggestedAirports($response, function ($airports) {
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
        $this->assertSuggestedAirports($response, function ($airports) {
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
        $this->assertSuggestedAirports($response, function ($airports) {
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
        $this->assertSuggestedAirports($response, function ($airports) {
            return $airports->isNotEmpty()
                && $airports->pluck('icao')->doesntContain('ENTC');
        });
    }

    // -------------------------------------------------------------------------
    // Route search
    // -------------------------------------------------------------------------

    public function test_route_search_lists_the_flights_and_draws_the_route(): void
    {
        $airline = (new Airline)->forceFill(['name' => 'Scandinavian', 'iata_code' => 'SK', 'icao_code' => 'SAS']);
        $airline->save();

        (new Flight)->forceFill([
            'airline_icao' => 'SAS',
            'airline_iata' => 'SK',
            'flight_number' => '4321',
            'flight_icao' => 'SAS4321',
            'airport_dep_id' => Airport::where('icao', 'ENGM')->value('id'),
            'dep_icao' => 'ENGM',
            'airport_arr_id' => Airport::where('icao', 'EDDF')->value('id'),
            'arr_icao' => 'EDDF',
            'last_aircraft_icao' => 'B738',
            'seen_counter' => 10,
            'first_seen_at' => '2026-01-02 00:00:00',
            'last_seen_at' => '2026-11-01 00:00:00',
        ])->save();

        $response = $this->get('/search/routes?departure=ENGM&arrival=EDDF&sort=flight');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page->component('SearchRoutes')
            ->where('departure.icao', 'ENGM')
            ->where('arrival.icao', 'EDDF')
            ->where('dataSince', '2026-01-02')
            ->where('routes.0.flightIcao', 'SAS4321')
            ->where('routes.0.airline.iata', 'SK')
            ->has('airportMapData.ENGM')
            ->has('airportMapData.EDDF'));
    }

    public function test_route_search_without_flights_returns_to_the_form_with_its_input(): void
    {
        $response = $this->get('/search/routes?departure=ENGM&arrival=EDDF&sort=flight');

        $response->assertRedirect(route('front.routes'));
        $response->assertSessionHasErrors('routeNotFound');
        $response->assertSessionHasInput('departure', 'ENGM');
    }

    // -------------------------------------------------------------------------
    // Shell visits that end on a Blade page
    // -------------------------------------------------------------------------

    public function test_a_shell_visit_that_ends_on_a_blade_page_becomes_a_full_page_load(): void
    {
        $response = $this->withHeaders($this->inertiaHeaders())->get('/login');

        $response->assertStatus(409);
        $response->assertHeader('X-Inertia-Location', url('/login'));
    }

    public function test_flashed_data_survives_the_full_page_load_to_a_blade_page(): void
    {
        $this->withHeaders($this->inertiaHeaders())->get('/search?' . http_build_query(array_merge($this->validSearchParams, [
            'metcondition' => 'INVALID',
        ])))->assertRedirect(route('front'));

        // Without the reflash this request would use the errors up, and the full load would show none
        $this->withHeaders($this->inertiaHeaders())->get('/login')
            ->assertStatus(409)
            ->assertSessionHasErrors('metcondition');
    }

    public function test_the_page_url_keeps_the_query_exactly_as_requested(): void
    {
        $query = 'icao=ENGM&direction=departure&destinations%5B%5D=C-EU&codeletter=JM';

        $this->assertSame('/?' . $query, $this->get('/?' . $query)->viewData('page')['url']);
    }

    public function test_a_failed_shell_search_lands_on_the_form_with_its_errors(): void
    {
        $this->withHeaders($this->inertiaHeaders())->get('/search?' . http_build_query(array_merge($this->validSearchParams, [
            'metcondition' => 'INVALID',
        ])))->assertRedirect(route('front'));

        $this->withHeaders($this->inertiaHeaders())->get(route('front'))
            ->assertOk()
            ->assertJsonPath('component', 'SearchForm')
            ->assertJsonPath('props.old.metcondition', 'INVALID')
            ->assertJsonPath('props.errors.metcondition', fn ($message) => filled($message));
    }

    private function inertiaHeaders(): array
    {
        return ['X-Inertia' => 'true', 'X-Inertia-Version' => app(HandleInertiaRequests::class)->version(request())];
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

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * The suggestions as Airport models, in page order, carrying the search's own distance,
     * airtime and score_count, so assertions can read any airport column
     */
    private function suggestedAirports(TestResponse $response): Collection
    {
        $response->assertOk()->assertInertia(fn (Assert $page) => $page->component('SearchResults'));

        $suggestions = collect($response->inertiaProps('suggestions'));
        $models = Airport::whereIn('icao', $suggestions->pluck('icao'))->get()->keyBy('icao');

        return $suggestions->map(fn (array $suggestion) => $models[$suggestion['icao']]->forceFill([
            'distance' => $suggestion['distance'],
            'airtime' => $suggestion['airtime'],
            'score_count' => $suggestion['scoreCount'],
        ]));
    }

    private function assertSuggestedAirports(TestResponse $response, callable $assertion): void
    {
        $this->assertTrue($assertion($this->suggestedAirports($response)), 'The suggested airports did not match');
    }

    private function assertPrimaryAirport(TestResponse $response, callable $assertion): void
    {
        $response->assertOk()->assertInertia(fn (Assert $page) => $page->component('SearchResults'));

        $this->assertTrue($assertion(Airport::where('icao', $response->inertiaProps('primaryAirport.icao'))->first()), 'The primary airport did not match');
    }
}
