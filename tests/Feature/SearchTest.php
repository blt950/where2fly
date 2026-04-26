<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\TestAirportSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TestAirportSeeder::class);
    }
    private array $validSearchParams = [
        'icao'                      => 'ENGM',
        'direction'                 => 'departure',
        'destinations'              => ['Anywhere'],
        'codeletter'                => 'JM',
        'airtimeMin'                => 0,
        'airtimeMax'                => 5,
        'sortByWeather'             => 1,
        'sortByATC'                 => 1,
        'scores'                    => [
            'METAR_WINDY'           => 0,
            'METAR_GUSTS'           => 0,
            'METAR_CROSSWIND'       => 0,
            'METAR_SIGHT'           => 0,
            'METAR_RVR'             => 0,
            'METAR_CEILING'         => 0,
            'METAR_FOGGY'           => 0,
            'METAR_HEAVY_RAIN'      => 0,
            'METAR_HEAVY_SNOW'      => 0,
            'METAR_THUNDERSTORM'    => 0,
            'VATSIM_ATC'            => 0,
            'VATSIM_EVENT'          => 0,
            'VATSIM_POPULAR'        => 0,
        ],
        'metcondition'              => 'ANY',
        'destinationWithRoutesOnly' => 0,
        'destinationRunwayLights'   => 0,
        'destinationAirbases'       => -1,
        'flightDirection'           => 0,
        'destinationAirportSize'    => ['small_airport', 'medium_airport', 'large_airport'],
        'temperatureMin'            => -60,
        'temperatureMax'            => 60,
        'elevationMin'              => -2000,
        'elevationMax'              => 18000,
        'rwyLengthMin'              => 0,
        'rwyLengthMax'              => 17000,
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

    public function test_search_fails_with_unrealistic_flight_lenght_and_destinations(): void
    {
        $response = $this->get('/search?' . http_build_query(array_merge($this->validSearchParams, [
            'icao'         => 'ENGM',
            'airtimeMin'   => '1',
            'airtimeMax'   => '2',
            'destinations' => ['C-AS'],
        ])));

        $response->assertSessionHasErrors('airportNotFound');
    }

    public function test_search_doesnt_show_airport_without_metar(): void
    {
        $response = $this->get('/search?' . http_build_query(array_merge($this->validSearchParams, [
            'icao'         => 'ENBR',
            'destinations' => ['Domestic'],
            'airtimeMin'   => '0',
            'airtimeMax'   => '5',
        ])));

        $response->assertOk();
        $response->assertViewHas('suggestedAirports', function ($airports) {
            return $airports->pluck('icao')->doesntContain('ENSO');
        });
    }

    public function test_search_only_shows_domestic_when_searching_for_domestic(): void
    {
        $response = $this->get('/search?' . http_build_query(array_merge($this->validSearchParams, [
            'icao'         => 'ENBR',
            'destinations' => ['Domestic'],
            'airtimeMin'   => '0',
            'airtimeMax'   => '5',
        ])));

        $response->assertOk();
        $response->assertViewHas('suggestedAirports', function ($airports) {
            return $airports->pluck('iso_country')->unique()->count() === 1
                && $airports->first()->iso_country === 'NO';
        });
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
}
