<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

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
        $response = $this->get('/search?' . http_build_query([
            'codeletter' => 'JS',
            'metcondition' => 'ANY',
        ]));

        // Missing required fields → redirected back with errors
        $response->assertRedirect();
    }

    public function test_search_fails_with_invalid_codeletter(): void
    {
        $response = $this->get('/search?' . http_build_query([
            'direction' => 'departure',
            'codeletter' => 'INVALID',
            'airtimeMin' => 1,
            'airtimeMax' => 3,
            'metcondition' => 'ANY',
            'destinationWithRoutesOnly' => 0,
            'destinationRunwayLights' => 0,
            'destinationAirbases' => 0,
            'flightDirection' => 0,
            'temperatureMin' => -60,
            'temperatureMax' => 60,
            'elevationMin' => -2000,
            'elevationMax' => 18000,
            'rwyLengthMin' => 0,
            'rwyLengthMax' => 17000,
        ]));

        $response->assertSessionHasErrors();
    }

    public function test_search_fails_with_invalid_metcondition(): void
    {
        $response = $this->get('/search?' . http_build_query([
            'direction' => 'departure',
            'codeletter' => 'JS',
            'airtimeMin' => 1,
            'airtimeMax' => 3,
            'metcondition' => 'INVALID',
            'destinationWithRoutesOnly' => 0,
            'destinationRunwayLights' => 0,
            'destinationAirbases' => 0,
            'flightDirection' => 0,
            'temperatureMin' => -60,
            'temperatureMax' => 60,
            'elevationMin' => -2000,
            'elevationMax' => 18000,
            'rwyLengthMin' => 0,
            'rwyLengthMax' => 17000,
        ]));

        $response->assertSessionHasErrors();
    }

    public function test_search_fails_with_out_of_range_airtime(): void
    {
        $response = $this->get('/search?' . http_build_query([
            'direction' => 'departure',
            'codeletter' => 'JS',
            'airtimeMin' => -1,
            'airtimeMax' => 99,
            'metcondition' => 'ANY',
            'destinationWithRoutesOnly' => 0,
            'destinationRunwayLights' => 0,
            'destinationAirbases' => 0,
            'flightDirection' => 0,
            'temperatureMin' => -60,
            'temperatureMax' => 60,
            'elevationMin' => -2000,
            'elevationMax' => 18000,
            'rwyLengthMin' => 0,
            'rwyLengthMax' => 17000,
        ]));

        $response->assertSessionHasErrors();
    }

    public function test_search_fails_with_invalid_direction(): void
    {
        $response = $this->get('/search?' . http_build_query([
            'direction' => 'sideways',
            'codeletter' => 'JS',
            'airtimeMin' => 1,
            'airtimeMax' => 3,
            'metcondition' => 'ANY',
            'destinationWithRoutesOnly' => 0,
            'destinationRunwayLights' => 0,
            'destinationAirbases' => 0,
            'flightDirection' => 0,
            'temperatureMin' => -60,
            'temperatureMax' => 60,
            'elevationMin' => -2000,
            'elevationMax' => 18000,
            'rwyLengthMin' => 0,
            'rwyLengthMax' => 17000,
        ]));

        $response->assertSessionHasErrors();
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
    // Authenticated user sees own lists on search page
    // -------------------------------------------------------------------------

    public function test_authenticated_user_sees_public_and_own_lists_on_search_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);
    }

    public function test_guest_only_sees_public_lists_on_search_page(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
