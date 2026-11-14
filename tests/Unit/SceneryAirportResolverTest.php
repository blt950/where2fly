<?php

namespace Tests\Unit;

use App\Helpers\SceneryHelper;
use PHPUnit\Framework\TestCase;

class SceneryAirportResolverTest extends TestCase
{
    private function index(array $airports): array
    {
        $index = ['byIcao' => [], 'grid' => []];
        foreach ($airports as $icao => [$id, $lat, $lon]) {
            $index['byIcao'][$icao] = [$id, $lat, $lon, SceneryHelper::isIcaoShaped($icao)];
            $index['grid'][(int) floor($lat) . ':' . (int) floor($lon)][] = $icao;
        }

        return $index;
    }

    private function norway(): array
    {
        return $this->index([
            'ENBR' => [1, 60.2934, 5.2181],
            'ENSO' => [2, 59.7919, 5.3408],
            'ENGM' => [3, 60.1939, 11.1004],
        ]);
    }

    public function test_feed_code_wins_when_known(): void
    {
        $this->assertSame(['id' => 2, 'icao' => 'ENSO'], SceneryHelper::resolveAirport(' enso ', 'ENBR Bergen', 60.29, 5.22, $this->norway()));
        $this->assertSame('ENBR', SceneryHelper::resolveAirport('ZZZZ', 'ENBR Bergen', 60.29, 5.22, $this->norway())['icao']);
    }

    public function test_title_token_is_used_only_near_the_coordinates(): void
    {
        // ENSO is ~30 nm from the first point, so its token is ignored and the nearest airport wins
        $this->assertSame('ENBR', SceneryHelper::resolveAirport('', 'Stord ENSO (fictional)', 60.29, 5.22, $this->norway())['icao']);
        $this->assertSame('ENSO', SceneryHelper::resolveAirport('', 'Stord ENSO', 59.85, 5.30, $this->norway(), titleRadiusNm: 5, nearestRadiusNm: 0)['icao']);
    }

    public function test_closest_matching_title_token_wins(): void
    {
        $index = $this->index(['AAAA' => [1, 50.0, 10.0], 'BBBB' => [2, 50.03, 10.0]]);

        $this->assertSame('BBBB', SceneryHelper::resolveAirport(null, 'AAAA / BBBB', 50.03, 10.0, $index)['icao']);
    }

    public function test_falls_back_to_nearest_airport_within_radius(): void
    {
        $this->assertSame('ENBR', SceneryHelper::resolveAirport(null, 'Flesland', 60.31, 5.20, $this->norway())['icao']);
        $this->assertNull(SceneryHelper::resolveAirport(null, 'Flesland', 60.40, 5.22, $this->norway()));
    }

    public function test_icao_shaped_ident_beats_a_nearer_local_ident(): void
    {
        $index = $this->index(['H123' => [1, 50.0, 10.0], '1H5' => [2, 50.001, 10.0], 'US-0001' => [3, 50.0, 10.001]]);

        $this->assertSame('H123', SceneryHelper::resolveAirport(null, 'Strip', 50.001, 10.0, $index)['icao']);
        $this->assertSame('1H5', SceneryHelper::resolveAirport(null, 'Strip', 50.001, 10.0, $this->index(['1H5' => [2, 50.001, 10.0], 'US-0001' => [3, 50.0, 10.01]]))['icao']);
    }

    public function test_nearest_search_crosses_grid_cells_and_the_antimeridian(): void
    {
        $index = $this->index(['NFFA' => [1, -16.5, 179.99], 'NFFB' => [2, -17.001, 10.0]]);

        $this->assertSame('NFFA', SceneryHelper::resolveAirport(null, 'Island strip', -16.5, -179.99, $index)['icao']);
        $this->assertSame('NFFB', SceneryHelper::resolveAirport(null, 'Strip', -16.999, 10.0, $index)['icao']);
    }

    public function test_normalize_link_collapses_flightsim_paths(): void
    {
        $this->assertSame('flightsim.to/addon/1946', SceneryHelper::normalizeLink('https://de.flightsim.to/file/1946/old'));
        $this->assertSame('flightsim.to/addon/1946', SceneryHelper::normalizeLink('https://flightsim.to/addon/1946/enbr-bergen'));
        $this->assertSame('contrail.shop/products/eddm', SceneryHelper::normalizeLink('https://www.Contrail.shop/products/eddm/'));
        $this->assertNull(SceneryHelper::flightsimAddonId('https://store.flightsim.to/product/efiv-ivalo-airport'));
    }
}
