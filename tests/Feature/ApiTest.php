<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Simulator;
use App\Models\User;
use App\Models\UserList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Create an API key that accepts requests from any IP.
     */
    private function createApiKey(): ApiKey
    {
        return ApiKey::create([
            'key' => 'test-api-key-' . uniqid(),
            'name' => 'Test Key',
            'ip_address' => '*',
            'disabled' => false,
        ]);
    }

    // -------------------------------------------------------------------------
    // Authentication check endpoint (no token required)
    // -------------------------------------------------------------------------

    public function test_authenticated_check_returns_false_for_guest(): void
    {
        $response = $this->getJson('/api/user/authenticated');

        $response->assertStatus(200)
            ->assertJson(['data' => false]);
    }

    public function test_authenticated_check_returns_true_for_logged_in_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/user/authenticated');

        $response->assertStatus(200)
            ->assertJson(['data' => true]);
    }

    // -------------------------------------------------------------------------
    // Airport map data endpoint (no token required)
    // -------------------------------------------------------------------------

    public function test_get_mapdata_from_known_icao_returns_success(): void
    {
        $response = $this->postJson('/api/mapdata/icao', ['icao' => 'KLAX']);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Success');
    }

    public function test_get_mapdata_from_unknown_icao_returns_validation_error(): void
    {
        $response = $this->postJson('/api/mapdata/icao', ['icao' => 'ZZZZ']);

        // 'exists:airports,icao' validation fails → 422
        $response->assertStatus(422);
    }

    public function test_get_mapdata_requires_icao_field(): void
    {
        $response = $this->postJson('/api/mapdata/icao', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('icao');
    }

    // -------------------------------------------------------------------------
    // Protected API search (requires bearer token)
    // -------------------------------------------------------------------------

    public function test_api_search_requires_valid_token(): void
    {
        $response = $this->postJson('/api/search', [
            'codeletter' => 'JS',
            'departure' => 'KLAX',
        ]);

        $response->assertStatus(401);
    }

    public function test_api_search_is_rejected_with_disabled_token(): void
    {
        $key = $this->createApiKey();
        $key->disabled = true;
        $key->save();

        $response = $this->withToken($key->key)->postJson('/api/search', [
            'codeletter' => 'JS',
            'departure' => 'KLAX',
        ]);

        $response->assertStatus(403);
    }

    public function test_api_search_fails_validation_without_codeletter(): void
    {
        $key = $this->createApiKey();

        $response = $this->withToken($key->key)->postJson('/api/search', [
            'departure' => 'KLAX',
        ]);

        // Missing required 'codeletter' → 422 unprocessable
        $response->assertStatus(422);
    }

    public function test_api_search_returns_error_when_both_arrival_and_departure_given(): void
    {
        $key = $this->createApiKey();

        $response = $this->withToken($key->key)->postJson('/api/search', [
            'codeletter' => 'JS',
            'departure' => 'KLAX',
            'arrival' => 'KSFO',
        ]);

        $response->assertStatus(400);
    }

    public function test_api_search_returns_error_when_neither_arrival_nor_departure_given(): void
    {
        $key = $this->createApiKey();

        $response = $this->withToken($key->key)->postJson('/api/search', [
            'codeletter' => 'JS',
        ]);

        $response->assertStatus(400);
    }

    // -------------------------------------------------------------------------
    // Authenticated list airports endpoint
    // -------------------------------------------------------------------------

    public function test_list_airports_endpoint_requires_auth(): void
    {
        $response = $this->getJson('/api/lists/airports');

        $response->assertStatus(401);
    }

    public function test_list_airports_endpoint_returns_success_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $simulator = Simulator::first();
        UserList::create([
            'name' => 'API Test List',
            'color' => '#FF0000',
            'simulator_id' => $simulator->id,
            'user_id' => $user->id,
            'public' => false,
            'hidden' => false,
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/lists/airports');

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Success');
    }

    // -------------------------------------------------------------------------
    // Fetching data fails gracefully
    // -------------------------------------------------------------------------

    public function test_airport_endpoint_returns_422_for_nonexistent_airport_id(): void
    {
        $response = $this->postJson('/api/airport', [
            'secondaryAirport' => 999999,
        ]);

        // 'exists:airports,id' fails → 422
        $response->assertStatus(422);
    }

    public function test_airport_endpoint_returns_422_when_required_field_is_missing(): void
    {
        $response = $this->postJson('/api/airport', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('secondaryAirport');
    }
}
