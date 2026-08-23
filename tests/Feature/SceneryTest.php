<?php

namespace Tests\Feature;

use App\Models\Scenery;
use App\Models\SceneryDeveloper;
use App\Models\Simulator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
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

    // -------------------------------------------------------------------------
    // Map scenery endpoint / FSAddonCompare fallback
    // -------------------------------------------------------------------------

    private function cacheScenery(string $developer, int $simulatorId, ?int $sourceReferenceId, string $link): Scenery
    {
        $developerModel = SceneryDeveloper::firstOrCreate([
            'icao' => 'EDDM',
            'developer' => $developer,
        ], [
            'airport_id' => $this->airports['EDDM']->id,
        ]);

        return Scenery::create([
            'scenery_developer_id' => $developerModel->id,
            'simulator_id' => $simulatorId,
            'link' => $link,
            'payware' => true,
            'published' => true,
            'source' => $sourceReferenceId ? 'fsaddoncompare' : 'where2fly',
            'source_reference_id' => $sourceReferenceId,
        ]);
    }

    private function fsacProduct(int $id, string $developer): array
    {
        return [
            'id' => $id,
            'developer' => $developer,
            'name' => $developer . ' EDDM',
            'link' => 'https://www.fsaddoncompare.com/product/' . $id . '/EDDM',
            'ratingAverage' => 4.5,
            'simulatorVersions' => ['MSFS2020'],
            'prices' => [[
                'store' => 'Contrail',
                'link' => 'https://r.fsaddoncompare.com?url=https%3A%2F%2Fcontrail.shop%2Fproducts%2Feddm-' . $id,
                'simulatorVersions' => ['MSFS2020'],
                'currencyPrice' => ['AUD' => 34.84, 'CAD' => 34.39, 'EUR' => 20.97, 'GBP' => 18.31, 'USD' => 24.98],
                'isDeveloper' => false,
            ]],
        ];
    }

    private function fakeFsac(array $results): void
    {
        Http::fake(['api.fsaddoncompare.com/*' => Http::response([
            'metadata' => ['total' => count($results), 'page' => 1, 'results' => count($results)],
            'results' => $results ?: null,
        ], 200)]);
    }

    private function getScenery()
    {
        return $this->postJson(route('api.airport.scenery'), ['airportIcao' => 'EDDM']);
    }

    public function test_cached_scenery_is_returned_when_fsac_knows_nothing_about_the_airport(): void
    {
        // A delisted ICAO still answers 200, with a null results payload
        $this->cacheScenery('Burning Blue Design', 1, 2758, 'https://orbxdirect.com/product/eddm-msfs');
        $this->fakeFsac([]);

        $response = $this->getScenery();

        $response->assertStatus(200);
        $response->assertJsonPath('data.MSFS2020.0.developer', 'Burning Blue Design');
        $response->assertJsonPath('data.MSFS2020.0.fsac', false);
    }

    public function test_cached_scenery_is_returned_when_fsac_errors(): void
    {
        $this->cacheScenery('Burning Blue Design', 1, 2758, 'https://orbxdirect.com/product/eddm-msfs');
        Http::fake(['api.fsaddoncompare.com/*' => Http::response('Not Found', 404)]);

        $response = $this->getScenery();

        $response->assertStatus(200);
        $response->assertJsonPath('data.MSFS2020.0.developer', 'Burning Blue Design');
    }

    public function test_cached_scenery_is_returned_when_fsac_is_unreachable(): void
    {
        $this->cacheScenery('Burning Blue Design', 1, 2758, 'https://orbxdirect.com/product/eddm-msfs');
        Http::fake(fn () => throw new ConnectionException('cURL error 6: Could not resolve host'));

        $response = $this->getScenery();

        $response->assertStatus(200);
        $response->assertJsonPath('data.MSFS2020.0.developer', 'Burning Blue Design');
    }

    public function test_delisted_product_falls_back_to_cache_while_listed_products_come_from_fsac(): void
    {
        $this->cacheScenery('sim-wings', 1, 4270, 'https://contrail.shop/products/eddm-4270');
        $this->cacheScenery('Burning Blue Design', 1, 2758, 'https://orbxdirect.com/product/eddm-msfs');
        $this->fakeFsac([$this->fsacProduct(4270, 'sim-wings')]);

        $response = $this->getScenery();

        $response->assertStatus(200);
        $sceneries = collect($response->json('data.MSFS2020'));
        $this->assertEquals(2, $sceneries->count());
        $this->assertTrue($sceneries->firstWhere('developer', 'sim-wings')['fsac']);
        $this->assertFalse($sceneries->firstWhere('developer', 'Burning Blue Design')['fsac']);
    }

    public function test_product_returned_by_fsac_is_not_duplicated_by_its_cached_copy(): void
    {
        $this->cacheScenery('sim-wings', 1, 4270, 'https://contrail.shop/products/eddm-4270');
        $this->fakeFsac([$this->fsacProduct(4270, 'sim-wings')]);

        $response = $this->getScenery();

        $response->assertStatus(200);
        $this->assertEquals(1, count($response->json('data.MSFS2020')));
        $response->assertJsonPath('data.MSFS2020.0.fsac', true);
    }

    public function test_scenery_endpoint_returns_404_when_nothing_is_cached_and_fsac_is_empty(): void
    {
        $this->fakeFsac([]);

        $this->getScenery()->assertStatus(404);
    }

    public function test_unpublished_cached_scenery_is_not_returned_on_fsac_miss(): void
    {
        $scenery = $this->cacheScenery('Burning Blue Design', 1, 2758, 'https://orbxdirect.com/product/eddm-msfs');
        $scenery->update(['published' => false]);
        $this->fakeFsac([]);

        $this->getScenery()->assertStatus(404);
    }
}
