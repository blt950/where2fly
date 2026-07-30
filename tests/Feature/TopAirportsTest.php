<?php

namespace Tests\Feature;

use App\Models\Airport;
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
        AirportScore::getTopAirports(null, null, 30, null, 'JM');

        $this->assertTrue(Cache::has('top-airports:EU:none:any:30'));
        $this->assertTrue(Cache::has('top-airports:all:vatsim:any:30'));
        $this->assertTrue(Cache::has('top-airports:all:none:JM:30'));
    }

    public function test_aircraft_filter_excludes_airports_with_short_runways(): void
    {
        Cache::flush();

        // Shorten EDDB's runway below the JM minimum (5000ft)
        $eddb = Airport::where('icao', 'EDDB')->first();
        $eddb->runways()->update(['length_ft' => 3000]);

        $unfiltered = AirportScore::getTopAirports()->pluck('airport.icao');
        Cache::flush();
        $filtered = AirportScore::getTopAirports(null, null, 30, null, 'JM')->pluck('airport.icao');

        $this->assertTrue($unfiltered->contains('EDDB'));
        $this->assertFalse($filtered->contains('EDDB'));
        $this->assertTrue($filtered->isNotEmpty());
    }

    public function test_top_page_loads_with_aircraft_filter(): void
    {
        $response = $this->get('/top?aircraft=JL');

        $response->assertStatus(200);
        $response->assertViewHas('aircraft', 'JL');
    }

    public function test_top_page_defaults_to_jm_aircraft(): void
    {
        $response = $this->get('/top');

        $response->assertStatus(200);
        $response->assertViewHas('aircraft', 'JM');
    }

    public function test_top_page_aircraft_all_disables_the_filter(): void
    {
        $response = $this->get('/top?aircraft=all');

        $response->assertStatus(200);
        $response->assertViewHas('aircraft', null);
    }

    public function test_top_page_invalid_aircraft_falls_back_to_default(): void
    {
        $response = $this->get('/top?aircraft=INVALID');

        $response->assertStatus(200);
        $response->assertViewHas('aircraft', 'JM');
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

        $this->assertCount(0, Cache::get('top-airports:all:none:any:30', []));
        $this->assertFalse(Cache::has('top-airports:all:none:any:30'));
    }
}
