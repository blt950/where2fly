<?php

namespace Tests\Feature;

use App\Models\Airport;
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

    public function test_api_search_rejects_unknown_score_reason(): void
    {
        $key = $this->createApiKey();

        // An unknown reason makes every EXISTS chain unsatisfiable — it must
        // fail validation instead of running a guaranteed-empty search
        $response = $this->withToken($key->key)->postJson('/api/search', [
            'codeletter' => 'JS',
            'departure' => 'KLAX',
            'scores' => ['METAR_VATSIM_ATC' => 1],
        ]);

        $response->assertStatus(422);
    }

    public function test_api_search_rejects_non_array_scores(): void
    {
        $key = $this->createApiKey();

        $response = $this->withToken($key->key)->postJson('/api/search', [
            'codeletter' => 'JS',
            'departure' => 'KLAX',
            'scores' => 'foo',
        ]);

        $response->assertStatus(422);
    }

    public function test_api_search_rejects_out_of_range_score_value(): void
    {
        $key = $this->createApiKey();

        $response = $this->withToken($key->key)->postJson('/api/search', [
            'codeletter' => 'JS',
            'departure' => 'KLAX',
            'scores' => ['VATSIM_ATC' => 5],
        ]);

        $response->assertStatus(422);
    }

    public function test_api_search_accepts_valid_scores(): void
    {
        $key = $this->createApiKey();

        $response = $this->withToken($key->key)->postJson('/api/search', [
            'codeletter' => 'JS',
            'departure' => 'KLAX',
            'scores' => ['VATSIM_ATC' => 1, 'METAR_FOGGY' => -1],
        ]);

        $response->assertStatus(200);
    }

    public function test_api_search_restricts_results_to_distance_bounds(): void
    {
        $key = $this->createApiKey();

        $response = $this->withToken($key->key)->postJson('/api/search', [
            'codeletter' => 'JM',
            'departure' => 'ENGM',
            'distanceMin' => 300,
            'distanceMax' => 500,
        ]);

        $response->assertStatus(200);

        $arrivals = collect($response->json('data.arrivals'));
        $this->assertTrue($arrivals->isNotEmpty());
        $this->assertTrue($arrivals->every(fn ($a) => $a['distanceNm'] >= 300 && $a['distanceNm'] <= 500));
    }

    public function test_api_search_rejects_negative_distance(): void
    {
        $key = $this->createApiKey();

        $response = $this->withToken($key->key)->postJson('/api/search', [
            'codeletter' => 'JM',
            'departure' => 'ENGM',
            'distanceMin' => -100,
        ]);

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

    public function test_list_airports_endpoint_groups_airports_per_list(): void
    {
        $user = User::factory()->create();
        $simulator = Simulator::first();
        $airport = Airport::first();

        $list = UserList::create([
            'name' => 'Grouped List',
            'color' => '#00FF00',
            'simulator_id' => $simulator->id,
            'user_id' => $user->id,
            'public' => false,
            'hidden' => false,
        ]);
        $list->airports()->attach($airport->id);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/lists/airports');

        // The map toggles each list on its own, so identity has to survive the response.
        $response->assertStatus(200)
            ->assertJsonPath('data.0.id', $list->id)
            ->assertJsonPath('data.0.name', 'Grouped List')
            ->assertJsonPath('data.0.color', '#00FF00')
            ->assertJsonPath('data.0.airports.' . $airport->icao . '.icao', $airport->icao)
            ->assertJsonPath('data.0.airports.' . $airport->icao . '.color', '#00FF00');
    }

    public function test_list_airports_endpoint_excludes_hidden_lists(): void
    {
        $user = User::factory()->create();
        $simulator = Simulator::first();

        UserList::create([
            'name' => 'Hidden List',
            'color' => '#0000FF',
            'simulator_id' => $simulator->id,
            'user_id' => $user->id,
            'public' => false,
            'hidden' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/lists/airports');

        $response->assertStatus(200)->assertJsonCount(0, 'data');
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
