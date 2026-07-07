<?php

namespace Tests\Feature;

use App\Models\AirportScore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TopAirportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_top_page_loads(): void
    {
        $response = $this->get('/top');

        $response->assertStatus(200);
    }

    public function test_top_airports_are_cached_per_variant(): void
    {
        Cache::flush();

        AirportScore::getTopAirports('EU');
        AirportScore::getTopAirports(null, null, 30, 'vatsim');

        $this->assertTrue(Cache::has('top-airports:EU:none:30'));
        $this->assertTrue(Cache::has('top-airports:all:vatsim:30'));
    }

    public function test_cached_result_is_served_without_recomputing(): void
    {
        Cache::flush();

        $first = AirportScore::getTopAirports('EU');

        // Remove every score — a cache hit must still return the first result
        AirportScore::query()->delete();
        $second = AirportScore::getTopAirports('EU');

        $this->assertEquals($first->pluck('airport_id'), $second->pluck('airport_id'));
    }

    public function test_whitelist_variant_bypasses_the_cache(): void
    {
        Cache::flush();

        AirportScore::getTopAirports(null, ['ENGM', 'ENBR']);

        $this->assertCount(0, Cache::get('top-airports:all:none:30', []));
        $this->assertFalse(Cache::has('top-airports:all:none:30'));
    }
}
