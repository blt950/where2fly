<?php

namespace Tests\Feature;

use App\Models\Airport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MatanYadaev\EloquentSpatial\Enums\Srid;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Tests\TestCase;

/**
 * The withinDistance scope combines a spatial-index bounding-box pre-filter
 * with the exact ST_Distance_Sphere checks. The pre-filter must never drop an
 * airport the exact check would keep — these tests pin that down at the
 * geometries where a naive box goes wrong (high latitude, antimeridian,
 * near-polar, global radii).
 */
class WithinDistanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_high_latitude_anchor_keeps_same_latitude_neighbors(): void
    {
        // At 60°N a circle's east-west extent spans far more longitude than at
        // the equator — an undersized box drops XHIB (~419 nm due east)
        $anchor = $this->makeAirport('XHIA', 60.0, 5.0);
        $this->makeAirport('XHIB', 60.0, 19.0);  // ~419 nm
        $this->makeAirport('XHIC', 60.0, 35.0);  // ~891 nm
        $this->makeAirport('XHID', 55.0, 5.0);   // ~300 nm due south

        $icaos = $this->icaosWithin($anchor, 0, 450);

        $this->assertContains('XHIB', $icaos);
        $this->assertContains('XHID', $icaos);
        $this->assertNotContains('XHIC', $icaos);
    }

    public function test_min_distance_excludes_close_airports(): void
    {
        $anchor = $this->makeAirport('XHIA', 60.0, 5.0);
        $this->makeAirport('XHIB', 60.0, 19.0);  // ~419 nm
        $this->makeAirport('XHIC', 60.0, 35.0);  // ~891 nm

        $icaos = $this->icaosWithin($anchor, 500, 1000);

        $this->assertNotContains('XHIB', $icaos);
        $this->assertContains('XHIC', $icaos);
    }

    public function test_antimeridian_anchor_keeps_neighbors_across_the_dateline(): void
    {
        $anchor = $this->makeAirport('XAMA', 0.0, 179.5);
        $this->makeAirport('XAMB', 0.0, -179.5); // ~60 nm across the antimeridian
        $this->makeAirport('XAMC', 0.0, 170.0);  // ~570 nm

        $icaos = $this->icaosWithin($anchor, 0, 200);

        $this->assertContains('XAMB', $icaos);
        $this->assertNotContains('XAMC', $icaos);
    }

    public function test_near_polar_anchor_returns_correct_distances(): void
    {
        $anchor = $this->makeAirport('XPLA', 84.0, 15.0);
        $this->makeAirport('XPLB', 79.0, 15.0);  // ~300 nm due south
        $this->makeAirport('XPLC', 70.0, 15.0);  // ~840 nm

        $icaos = $this->icaosWithin($anchor, 0, 400);

        $this->assertContains('XPLB', $icaos);
        $this->assertNotContains('XPLC', $icaos);
    }

    public function test_global_radius_reaches_other_continents(): void
    {
        // ~5,150 nm to the seeded KLAX — beyond the pre-filter's 4,000 nm cutoff
        $anchor = $this->makeAirport('XGLO', 50.0, 10.0);

        $icaos = $this->icaosWithin($anchor, 0, 6000);

        $this->assertContains('KLAX', $icaos);
    }

    private function makeAirport(string $icao, float $lat, float $lon): Airport
    {
        return Airport::create([
            'icao' => $icao,
            'name' => $icao,
            'type' => 'large_airport',
            'latitude_deg' => $lat,
            'longitude_deg' => $lon,
            'continent' => 'EU',
            'iso_country' => 'NO',
            'iso_region' => 'NO-01',
            'municipality' => $icao,
            'scheduled_service' => 'yes',
            'w2f_has_open_runway' => true,
            'w2f_airforcebase' => false,
            'coordinates' => new Point($lat, $lon, Srid::WGS84->value),
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function icaosWithin(Airport $anchor, float $minDistance, float $maxDistance): array
    {
        return Airport::withinDistance($anchor, $minDistance, $maxDistance, $anchor->icao)
            ->notIcao($anchor->icao)
            ->pluck('icao')
            ->all();
    }
}
