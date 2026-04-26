<?php

namespace Tests\Feature;

use App\Models\Scenery;
use App\Models\SceneryDeveloper;
use App\Models\Simulator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SceneryTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Create page
    // -------------------------------------------------------------------------

    public function test_scenery_create_page_requires_authentication(): void
    {
        $response = $this->get('/scenery/create');

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_scenery_create_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/scenery/create');

        $response->assertStatus(200);
    }

    public function test_scenery_create_page_shows_existing_developers_for_known_icao(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/scenery/create?airport=EDDM');

        $response->assertStatus(200);
    }

    // -------------------------------------------------------------------------
    // Store suggestion
    // -------------------------------------------------------------------------

    public function test_user_can_submit_a_scenery_suggestion(): void
    {
        $user = User::factory()->create();
        $simulator = Simulator::first();

        $response = $this->actingAs($user)->post('/scenery/create', [
            'icao' => 'EDDM',
            'developer' => 'Aerosoft',
            'link' => 'https://www.aerosoft.com/eddm',
            'payware' => '1',
            'simulators' => [$simulator->id],
        ]);

        $response->assertRedirect(route('scenery.create'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('scenery_developers', [
            'icao' => 'EDDM',
            'developer' => 'Aerosoft',
        ]);
    }

    public function test_scenery_suggestion_is_stored_as_unpublished(): void
    {
        $user = User::factory()->create();
        $simulator = Simulator::first();

        $this->actingAs($user)->post('/scenery/create', [
            'icao' => 'EDDM',
            'developer' => 'Orbx',
            'link' => 'https://orbxdirect.com/eddm',
            'payware' => '1',
            'simulators' => [$simulator->id],
        ]);

        $developer = SceneryDeveloper::where('developer', 'Orbx')->where('icao', 'EDDM')->first();
        $this->assertNotNull($developer);

        $scenery = Scenery::where('scenery_developer_id', $developer->id)->first();
        $this->assertNotNull($scenery);
        $this->assertFalse((bool) $scenery->published);
    }

    public function test_scenery_suggestion_fails_with_invalid_icao(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/scenery/create', [
            'icao' => 'ZZZZ', // does not exist in airports table
            'developer' => 'SomeDev',
            'link' => 'https://example.com',
            'payware' => '0',
            'simulators' => [1],
        ]);

        $response->assertSessionHasErrors('icao');
    }

    public function test_scenery_suggestion_fails_with_invalid_url(): void
    {
        $user = User::factory()->create();
        $simulator = Simulator::first();

        $response = $this->actingAs($user)->post('/scenery/create', [
            'icao' => 'EDDM',
            'developer' => 'BadLinkDev',
            'link' => 'not-a-url',
            'payware' => '0',
            'simulators' => [$simulator->id],
        ]);

        $response->assertSessionHasErrors('link');
    }

    public function test_scenery_suggestion_requires_at_least_one_simulator(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/scenery/create', [
            'icao' => 'EDDM',
            'developer' => 'NoSimDev',
            'link' => 'https://example.com',
            'payware' => '0',
            // simulators intentionally omitted
        ]);

        $response->assertSessionHasErrors('simulators');
    }

    public function test_scenery_suggestion_associates_suggested_by_user(): void
    {
        $user = User::factory()->create();
        $simulator = Simulator::first();

        $this->actingAs($user)->post('/scenery/create', [
            'icao' => 'EDDM',
            'developer' => 'UserSceneryDev',
            'link' => 'https://userscenery.com',
            'payware' => '0',
            'simulators' => [$simulator->id],
        ]);

        $developer = SceneryDeveloper::where('developer', 'UserSceneryDev')->first();
        $scenery = Scenery::where('scenery_developer_id', $developer->id)->first();

        $this->assertEquals($user->id, $scenery->suggested_by_user_id);
    }

    public function test_duplicate_developer_is_reused_for_new_simulator(): void
    {
        $user = User::factory()->create();
        $simulators = Simulator::take(2)->get();

        // First suggestion
        $this->actingAs($user)->post('/scenery/create', [
            'icao' => 'EDDM',
            'developer' => 'SharedDev',
            'link' => 'https://shared.com',
            'payware' => '0',
            'simulators' => [$simulators[0]->id],
        ]);

        // Second suggestion with same developer – should not create a new SceneryDeveloper row
        $this->actingAs($user)->post('/scenery/create', [
            'icao' => 'EDDM',
            'developer' => 'SharedDev',
            'link' => 'https://shared.com/v2',
            'payware' => '0',
            'simulators' => [$simulators[1]->id],
        ]);

        $count = SceneryDeveloper::where('developer', 'SharedDev')->where('icao', 'EDDM')->count();
        $this->assertEquals(1, $count);
    }
}
