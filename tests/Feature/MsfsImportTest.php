<?php

namespace Tests\Feature;

use App\Models\Airport;
use App\Models\Simulator;
use App\Models\User;
use App\Models\UserList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class MsfsImportTest extends TestCase
{
    use RefreshDatabase;

    private const FIXTURE = __DIR__ . '/../Fixtures/msfs2024-content.xml';

    private const COMMUNITY = ['EDDB', 'EGKK', 'EKVG', 'ENBG', 'ENBO', 'ENBR', 'ENBX', 'ENGM', 'ENSA', 'ENTC', 'ENVA', 'ENZV', 'ESSA'];

    /** Every ICAO an enabled `-airport-` package of the fixture names, plus the overrides and guesses */
    private function seedFixtureAirports(array $except = []): void
    {
        preg_match_all('/-airport-([a-z0-9]{3,4})-[^"]*" active="Activated"/', file_get_contents(self::FIXTURE), $matches);
        $icaos = array_diff(array_unique([...array_map('strtoupper', $matches[1]), 'EGKK', 'EDDK', 'EFHK', 'ZUDC']), ['ENXX'], $except);

        foreach (array_diff($icaos, Airport::whereIn('icao', $icaos)->pluck('icao')->all()) as $icao) {
            Airport::factory()->create(['icao' => $icao]);
        }
    }

    private function upload(string $contents, array $data = []): TestResponse
    {
        return $this->actingAs(User::factory()->create())->post(route('list.import.msfs'), [
            'content_xml' => UploadedFile::fake()->createWithContent('Content.xml', $contents),
        ] + $data, ['Accept' => 'application/json']);
    }

    public function test_guests_cannot_import(): void
    {
        $this->post(route('list.import.msfs'), ['content_xml' => UploadedFile::fake()->createWithContent('Content.xml', '<Packages/>')])
            ->assertRedirect(route('login'));
    }

    public function test_the_fixture_suggests_the_community_airports(): void
    {
        $this->seedFixtureAirports();

        $response = $this->upload(file_get_contents(self::FIXTURE))->assertOk();

        $response->assertJson([
            'suggestion' => implode("\n", self::COMMUNITY),
            'count' => 13,
            'include_native' => false,
            'errors' => [],
            'stats' => [
                'packages_total' => 1245,
                'enabled' => 1223,
                'disabled_by_state' => ['SystemDisabled' => 22],
                'duplicates_collapsed' => 0,
                'native_only_available' => 173,
                'states_seen' => ['Activated', 'SystemDisabled'],
            ],
        ]);
        $this->assertSame(self::COMMUNITY, array_column($response->json('airports'), 'icao'));

        $review = collect($response->json('needs_review'))->pluck('suggested_icao', 'package');
        $this->assertSame('EFHK', $review['communityfs20-efhk-helsinki']);
        $this->assertSame('ZUDC', $review['communityfs20-kui-zudc']);
        $this->assertSame('ZUDC', $review['communityfs20-kui-zudcsat']);
        $this->assertNull($review['communityfs20-fsltl-traffic-base']);
    }

    public function test_the_fixture_with_native_airports_included(): void
    {
        $this->seedFixtureAirports();

        $response = $this->upload(file_get_contents(self::FIXTURE), ['include_native' => '1'])->assertOk();
        $airports = collect($response->json('airports'));

        $this->assertSame(186, $response->json('count'));
        $this->assertCount(186, array_unique(explode("\n", $response->json('suggestion'))));
        $this->assertSame(0, $response->json('stats.native_only_available'));
        $this->assertSame(13, $airports->where('category', 'community')->count());
        $this->assertSame(173, $airports->where('category', 'native')->count());

        // 56 Asobo and 117 Microsoft through the -airport- rule, EDDK through its override
        $native = $airports->flatMap(fn ($airport) => $airport['packages'])->filter(fn ($package) => ! str_starts_with($package, 'community'));
        $this->assertSame(56, $native->filter(fn ($package) => str_contains($package, '-asobo-airport-'))->count());
        $this->assertSame(117, $native->filter(fn ($package) => str_contains($package, '-microsoft-airport-'))->count());
        $this->assertSame(['fs24-microsoft-eddk-cologne-bonn'], $airports->firstWhere('icao', 'EDDK')['packages']);

        $essa = $airports->firstWhere('icao', 'ESSA');
        $this->assertSame('community', $essa['category']);
        $this->assertTrue($essa['also_native']);
    }

    public function test_airports_missing_from_the_database_are_reported_not_suggested(): void
    {
        $this->seedFixtureAirports(except: ['C53', 'ENSA']);
        Airport::factory()->create(['icao' => 'KC53', 'local_code' => 'C53']);

        $response = $this->upload(file_get_contents(self::FIXTURE), ['include_native' => '1'])->assertOk();

        $this->assertSame(
            [['package' => 'communityfs24-blt950-airport-ensa-sandviken-helipad', 'icao' => 'ENSA', 'reason' => 'icao_not_in_database', 'category' => 'community']],
            $response->json('errors')
        );
        $this->assertContains('KC53', array_column($response->json('airports'), 'icao'));
        $this->assertNotContains('ENSA', array_column($response->json('airports'), 'icao'));
    }

    public function test_the_suggestion_saves_through_the_list_form_without_unknown_airports(): void
    {
        $this->seedFixtureAirports();
        $user = User::factory()->create();
        $suggestion = $this->upload(file_get_contents(self::FIXTURE), ['include_native' => '1'])->json('suggestion');

        $this->actingAs($user)->post('/lists/create', [
            'color' => '#ff0000', 'name' => 'Imported', 'simulator' => Simulator::first()->id, 'airports' => $suggestion,
        ])->assertRedirect(route('list.index'))->assertSessionHas('success')->assertSessionMissing('warning');

        $this->assertSame(186, UserList::where('name', 'Imported')->first()->airports()->count());
    }

    public function test_an_xml_declaration_is_accepted(): void
    {
        $this->upload('<?xml version="1.0" encoding="utf-8"?>' . "\n" . '<Packages><Package name="fs24-asobo-airport-kjfk-new-york-jfk" active="Activated"/></Packages>', ['include_native' => '1'])
            ->assertOk()->assertJson(['suggestion' => 'KJFK']);
    }

    public function test_a_load_order_file_is_refused(): void
    {
        $this->upload('<Priorities><Package name="fs-base" priority="1"/></Priorities>')
            ->assertStatus(422)->assertJson(['reason' => 'unexpected_root'])->assertJsonStructure(['message']);
    }

    public function test_a_doctype_is_refused(): void
    {
        $xml = '<!DOCTYPE Packages [<!ENTITY x SYSTEM "file:///etc/passwd">]><Packages><Package name="&x;" active="Activated"/></Packages>';

        $this->upload($xml)->assertStatus(422)->assertJson(['reason' => 'invalid_xml']);
    }

    public function test_a_file_that_is_not_xml_is_refused(): void
    {
        $this->upload('this is not xml')->assertStatus(422)->assertJson(['reason' => 'invalid_xml']);
        $this->upload('<Packages><Package name="a" active="Activated">')->assertStatus(422)->assertJson(['reason' => 'invalid_xml']);
    }

    public function test_a_file_without_packages_is_refused(): void
    {
        $this->upload('<Packages></Packages>')->assertStatus(422)->assertJson(['reason' => 'empty']);
    }

    public function test_a_file_over_the_size_limit_is_refused(): void
    {
        config(['msfs_scenery.max_upload_kb' => 1]);

        $this->upload('<Packages>' . str_repeat('<Package name="fs24-x" active="Activated"/>', 100) . '</Packages>')
            ->assertStatus(422)->assertJson(['reason' => 'too_large']);
    }

    public function test_a_missing_file_is_refused(): void
    {
        $this->actingAs(User::factory()->create())->post(route('list.import.msfs'), [], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJson(['reason' => 'invalid_request']);
    }
}
