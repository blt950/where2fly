<?php

namespace Tests\Feature;

use App\Console\Commands\FetchSceneries;
use App\Models\Airport;
use App\Models\Scenery;
use App\Models\SceneryDeveloper;
use App\Models\Simulator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use MatanYadaev\EloquentSpatial\Enums\Srid;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Tests\TestCase;

class FetchSceneriesTest extends TestCase
{
    use RefreshDatabase;

    private int $msfs2020;

    private int $msfs2024;

    protected function setUp(): void
    {
        parent::setUp();

        Sleep::fake();
        $this->msfs2020 = Simulator::where('shortened_name', 'MSFS2020')->value('id');
        $this->msfs2024 = Simulator::where('shortened_name', 'MSFS2024')->value('id');
    }

    private function item(int $id, string $title, float $lat, float $lon, string $compatibility = 'Flight Simulator 2020/24', array $overrides = []): array
    {
        return array_merge([
            'id' => (string) $id,
            'title' => $title,
            'code' => '',
            'developer' => 'dev' . $id,
            'latitude' => $lat,
            'longitude' => $lon,
            'compatibility' => $compatibility,
            'is_payware' => false,
            'link' => 'https://flightsim.to/addon/' . $id . '/slug-' . $id,
        ], $overrides);
    }

    /**
     * Items go into category 3. Every category also carries a payware filler so it counts as healthy.
     */
    private function fakeFlightsim(array $items, array $addonStatuses = [], array $failingCategories = []): void
    {
        Http::fake(function (Request $request) use ($items, $addonStatuses, $failingCategories) {
            if (str_contains($request->url(), '/map-data/')) {
                $category = (int) ($this->queryOf($request)['category'] ?? 0);
                if (in_array($category, $failingCategories)) {
                    return Http::response('Server Error', 500);
                }
                $data = $category === 3 ? $items : [];
                $data[] = $this->item(900000 + $category, 'Payware filler', 0, 0, overrides: ['is_payware' => true]);

                return Http::response(['success' => true, 'data' => $data]);
            }

            // Mirrors flightsim.to: a slug-less addon URL is the app shell (200) even for deleted addons
            if (! preg_match('~/addon/(\d+)/[^/]+~', $request->url(), $match)) {
                return Http::response('', 200);
            }

            return Http::response('', $addonStatuses[(int) $match[1]] ?? 200);
        });
    }

    private function queryOf(Request $request): array
    {
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

        return $query;
    }

    private function existingRow(int $simulatorId, int $addonId, array $attributes = [], string $icao = 'ENBR', string $developer = 'WombiiActual'): Scenery
    {
        $developerModel = SceneryDeveloper::firstOrCreate(
            ['icao' => $icao, 'developer' => $developer],
            ['airport_id' => Airport::where('icao', $icao)->value('id')],
        );

        return Scenery::create(array_merge([
            'scenery_developer_id' => $developerModel->id,
            'simulator_id' => $simulatorId,
            'link' => 'https://flightsim.to/addon/' . $addonId . '/slug-' . $addonId,
            'payware' => false,
            'published' => true,
            'source' => 'manual',
        ], $attributes));
    }

    private function addonRequests(): int
    {
        return Http::recorded(fn (Request $request) => str_contains($request->url(), '/addon/'))->count();
    }

    public function test_new_item_with_title_icao_creates_developer_and_one_row_per_simulator(): void
    {
        $this->fakeFlightsim([$this->item(1001, 'EDDM Munich Enhanced', 48.36, 11.79)]);

        $this->artisan('fetch:sceneries')->assertSuccessful();

        $developer = SceneryDeveloper::where('icao', 'EDDM')->where('developer', 'dev1001')->firstOrFail();
        $this->assertSame($this->airports['EDDM']->id, $developer->airport_id);
        $rows = Scenery::where('scenery_developer_id', $developer->id)->get();
        $this->assertEqualsCanonicalizing([$this->msfs2020, $this->msfs2024], $rows->pluck('simulator_id')->all());
        foreach ($rows as $row) {
            $this->assertSame('flightsim.to', $row->source);
            $this->assertSame(1001, (int) $row->source_reference_id);
            $this->assertTrue((bool) $row->published);
            $this->assertFalse((bool) $row->payware);
        }
    }

    public function test_item_without_title_icao_resolves_to_nearest_airport_and_unmatched_item_is_skipped(): void
    {
        $this->fakeFlightsim([
            $this->item(1002, 'Flesland Airport Scenery', 60.30, 5.22),
            $this->item(1003, 'Somewhere in the ocean', 10.0, -30.0),
        ]);

        $this->artisan('fetch:sceneries')->assertSuccessful();

        $this->assertSame('ENBR', Scenery::where('source_reference_id', 1002)->firstOrFail()->developer->icao);
        $this->assertFalse(Scenery::where('source_reference_id', 1003)->exists());
    }

    public function test_title_icao_far_from_the_coordinates_is_ignored(): void
    {
        // ENGM is ~170 nm from these coordinates — a stray token, not this scenery's airport
        $this->fakeFlightsim([$this->item(1004, 'ENGM style terminal for Bergen', 60.29, 5.22)]);

        $this->artisan('fetch:sceneries')->assertSuccessful();

        $this->assertSame('ENBR', Scenery::where('source_reference_id', 1004)->firstOrFail()->developer->icao);
    }

    public function test_feed_code_takes_precedence_and_unknown_code_falls_back(): void
    {
        $this->fakeFlightsim([
            $this->item(1005, 'ENBR Bergen', 60.29, 5.22, overrides: ['code' => 'enso']),
            $this->item(1006, 'ENBR Bergen', 60.29, 5.22, overrides: ['code' => 'ZZZZ']),
        ]);

        $this->artisan('fetch:sceneries')->assertSuccessful();

        $this->assertSame('ENSO', Scenery::where('source_reference_id', 1005)->firstOrFail()->developer->icao);
        $this->assertSame('ENBR', Scenery::where('source_reference_id', 1006)->firstOrFail()->developer->icao);
    }

    public function test_existing_manual_row_is_adopted_without_creating_duplicates(): void
    {
        $row = $this->existingRow($this->msfs2020, 1946);
        $this->fakeFlightsim([$this->item(1946, 'ENBR Bergen', 60.29, 5.22, 'Flight Simulator 2020', ['developer' => 'SomeoneElse'])]);

        $this->artisan('fetch:sceneries')->assertSuccessful();

        $row->refresh();
        $this->assertSame('flightsim.to', $row->source);
        $this->assertSame(1946, (int) $row->source_reference_id);
        $this->assertSame(1, Scenery::count());
        $this->assertFalse(SceneryDeveloper::where('developer', 'SomeoneElse')->exists());
    }

    public function test_user_contribution_is_adopted_and_keeps_its_suggester(): void
    {
        $user = User::factory()->create();
        $row = $this->existingRow($this->msfs2020, 1946, ['source' => 'user_contribution', 'suggested_by_user_id' => $user->id]);
        $this->fakeFlightsim([$this->item(1946, 'ENBR Bergen', 60.29, 5.22, 'Flight Simulator 2020')]);

        $this->artisan('fetch:sceneries')->assertSuccessful();

        $row->refresh();
        $this->assertSame('flightsim.to', $row->source);
        $this->assertSame($user->id, $row->suggested_by_user_id);
    }

    public function test_link_is_refreshed_from_the_feed(): void
    {
        $row = $this->existingRow($this->msfs2020, 1946, ['link' => 'https://de.flightsim.to/file/1946/old-slug']);
        $this->fakeFlightsim([$this->item(1946, 'ENBR Bergen', 60.29, 5.22, 'Flight Simulator 2020')]);

        $this->artisan('fetch:sceneries')->assertSuccessful();

        $this->assertSame('https://flightsim.to/addon/1946/slug-1946', $row->refresh()->link);
    }

    public function test_newly_compatible_simulator_is_added_and_inherits_hidden_state(): void
    {
        $row = $this->existingRow($this->msfs2020, 1946, ['published' => false]);
        $this->fakeFlightsim([$this->item(1946, 'ENBR Bergen', 60.29, 5.22, 'Flight Simulator 2020/24')]);

        $this->artisan('fetch:sceneries')->assertSuccessful();

        $added = Scenery::where('simulator_id', $this->msfs2024)->firstOrFail();
        $this->assertSame($row->scenery_developer_id, $added->scenery_developer_id);
        $this->assertSame(1946, (int) $added->source_reference_id);
        $this->assertFalse((bool) $added->published);
        $this->assertFalse((bool) $row->refresh()->published);
    }

    public function test_dropped_simulator_row_is_deleted(): void
    {
        $this->existingRow($this->msfs2020, 1946);
        $msfs2024Row = $this->existingRow($this->msfs2024, 1946);
        $this->fakeFlightsim([$this->item(1946, 'ENBR Bergen', 60.29, 5.22, 'Flight Simulator 2024')]);

        $this->artisan('fetch:sceneries')->assertSuccessful();

        $this->assertSame([$msfs2024Row->id], Scenery::pluck('id')->all());
    }

    public function test_payware_flag_follows_the_feed_and_fsac_rows_are_untouched(): void
    {
        $nowPayware = $this->existingRow($this->msfs2020, 2000);
        $nowFree = $this->existingRow($this->msfs2020, 2001, ['payware' => true]);
        $bundled = $this->existingRow($this->msfs2020, 2002, ['payware' => -1]);
        $fsacRow = $this->existingRow($this->msfs2020, 2000, [
            'source' => 'fsaddoncompare', 'source_reference_id' => 2000, 'payware' => false, 'link' => 'https://contrail.shop/products/enbr',
        ], developer: 'Payware Studio');
        $this->fakeFlightsim([
            $this->item(2000, 'ENBR Bergen', 60.29, 5.22, 'Flight Simulator 2020', ['is_payware' => true]),
            $this->item(2001, 'ENBR Bergen', 60.29, 5.22, 'Flight Simulator 2020'),
            $this->item(2002, 'ENBR Bergen', 60.29, 5.22, 'Flight Simulator 2020', ['is_payware' => true]),
        ]);

        $this->artisan('fetch:sceneries')->assertSuccessful();

        $this->assertSame(1, (int) $nowPayware->refresh()->payware);
        $this->assertSame('flightsim.to', $nowPayware->source);
        $this->assertSame(0, (int) $nowFree->refresh()->payware);
        $this->assertSame(-1, (int) $bundled->refresh()->payware);
        $this->assertSame(0, (int) $fsacRow->refresh()->payware);
        $this->assertSame('fsaddoncompare', $fsacRow->source);
    }

    public function test_new_payware_item_is_created_marked_as_payware(): void
    {
        $this->fakeFlightsim([$this->item(2003, 'EDDM Munich Pro', 48.36, 11.79, overrides: ['is_payware' => true])]);

        $this->artisan('fetch:sceneries')->assertSuccessful();

        $rows = Scenery::where('source_reference_id', 2003)->get();
        $this->assertCount(2, $rows);
        $this->assertTrue($rows->every(fn ($row) => (int) $row->payware === 1 && $row->published));
    }

    public function test_item_near_an_airport_with_a_local_ident_attaches_to_it(): void
    {
        Airport::create([
            'icao' => '09J', 'local_code' => '09J', 'name' => 'Jekyll Island Airport', 'type' => 'small_airport',
            'latitude_deg' => 31.0745, 'longitude_deg' => -81.4278, 'continent' => 'NA', 'iso_country' => 'US', 'iso_region' => 'US-GA',
            'municipality' => 'Jekyll Island', 'elevation_ft' => 11, 'scheduled_service' => 'no', 'w2f_has_open_runway' => true, 'w2f_airforcebase' => false,
            'coordinates' => new Point(31.0745, -81.4278, Srid::WGS84->value),
        ]);
        $this->fakeFlightsim([$this->item(2004, '09J – Jekyll Island Airport', 31.0760, -81.4270)]);

        $this->artisan('fetch:sceneries')->assertSuccessful();

        $this->assertSame('09J', Scenery::where('source_reference_id', 2004)->firstOrFail()->developer->icao);
    }

    public function test_unlisted_addons_are_deleted_only_when_their_page_is_gone(): void
    {
        $deleted = $this->existingRow($this->msfs2020, 3001);
        $live = $this->existingRow($this->msfs2020, 3002);
        $recentlyChecked = $this->existingRow($this->msfs2020, 3003, ['source_checked_at' => now()->subDays(5)]);
        $erroring = $this->existingRow($this->msfs2020, 3004);
        $this->fakeFlightsim([$this->item(1001, 'EDDM Munich', 48.36, 11.79)], [3001 => 404, 3002 => 200, 3003 => 404, 3004 => 500]);

        $this->artisan('fetch:sceneries')->assertSuccessful();

        $this->assertModelMissing($deleted);
        $live->refresh();
        $this->assertNotNull($live->source_checked_at);
        $this->assertSame('flightsim.to', $live->source);
        $this->assertSame(3002, (int) $live->source_reference_id);
        $this->assertModelExists($recentlyChecked);
        $this->assertNull($erroring->refresh()->source_checked_at);
        $this->assertSame(3, $this->addonRequests());
    }

    public function test_rate_limit_stops_the_probe(): void
    {
        $this->existingRow($this->msfs2020, 3001);
        $this->existingRow($this->msfs2020, 3002);
        $this->fakeFlightsim([$this->item(1001, 'EDDM Munich', 48.36, 11.79)], [3001 => 429, 3002 => 429]);

        $this->artisan('fetch:sceneries')->assertSuccessful();

        $this->assertSame(1, $this->addonRequests());
        $this->assertSame(2, Scenery::where('link', 'like', '%/300_/%')->whereNull('source_checked_at')->count());
    }

    public function test_probe_limit_caps_requests(): void
    {
        foreach ([3001, 3002, 3003] as $addonId) {
            $this->existingRow($this->msfs2020, $addonId);
        }
        $this->fakeFlightsim([$this->item(1001, 'EDDM Munich', 48.36, 11.79)]);

        $this->artisan('fetch:sceneries', ['--probe-limit' => 2])->assertSuccessful();

        $this->assertSame(2, $this->addonRequests());
    }

    public function test_failed_feed_skips_every_deletion_but_still_adds(): void
    {
        $this->existingRow($this->msfs2020, 1946);
        $this->existingRow($this->msfs2020, 3001);
        $this->fakeFlightsim([
            $this->item(1001, 'EDDM Munich', 48.36, 11.79),
            $this->item(1946, 'ENBR Bergen', 60.29, 5.22, 'Flight Simulator 2024'),
        ], [3001 => 404], failingCategories: [46]);

        $this->artisan('fetch:sceneries')->assertFailed();

        $this->assertTrue(Scenery::where('source_reference_id', 1001)->exists());
        $this->assertTrue(Scenery::where('source_reference_id', 1946)->where('simulator_id', $this->msfs2020)->exists());
        $this->assertTrue(Scenery::where('source_reference_id', 1946)->where('simulator_id', $this->msfs2024)->exists());
        $this->assertSame(1, Scenery::where('link', 'like', '%/3001/%')->count());
        $this->assertSame(0, $this->addonRequests());
    }

    public function test_unknown_compatibility_creates_nothing_and_leaves_existing_rows_alone(): void
    {
        $row = $this->existingRow($this->msfs2020, 1946);
        $this->fakeFlightsim([
            $this->item(1946, 'ENBR Bergen', 60.29, 5.22, 'Unknown'),
            $this->item(1007, 'EDDM Munich', 48.36, 11.79, 'Unknown'),
        ]);

        $this->artisan('fetch:sceneries')->assertSuccessful();

        $this->assertSame([$row->id], Scenery::pluck('id')->all());
        $this->assertSame(0, $this->addonRequests());
    }

    public function test_second_run_changes_nothing(): void
    {
        $this->existingRow($this->msfs2020, 1946);
        $this->fakeFlightsim([
            $this->item(1946, 'ENBR Bergen', 60.29, 5.22),
            $this->item(1001, 'EDDM Munich', 48.36, 11.79),
        ]);

        $this->artisan('fetch:sceneries')->assertSuccessful();
        $before = Scenery::orderBy('id')->get(['id', 'simulator_id', 'link', 'source', 'source_reference_id', 'updated_at'])->toArray();
        $this->travel(1)->minutes();
        $this->artisan('fetch:sceneries')->assertSuccessful();

        $this->assertSame($before, Scenery::orderBy('id')->get(['id', 'simulator_id', 'link', 'source', 'source_reference_id', 'updated_at'])->toArray());
        $this->assertSame(1, SceneryDeveloper::where('developer', 'dev1001')->count());
    }

    public function test_feed_urls_cover_every_category(): void
    {
        $this->fakeFlightsim([$this->item(1001, 'EDDM Munich', 48.36, 11.79)]);

        $this->artisan('fetch:sceneries')->assertSuccessful();

        foreach (FetchSceneries::CATEGORIES as $category) {
            Http::assertSent(fn (Request $request) => str_starts_with($request->url(), FetchSceneries::FEED_URL) && ($this->queryOf($request)['category'] ?? null) == $category && ! isset($this->queryOf($request)['developer']));
        }
    }
}
