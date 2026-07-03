<?php

namespace Tests\Unit;

use App\Models\Taf;
use PHPUnit\Framework\TestCase;

class TafTest extends TestCase
{
    public function test_wind_methods_read_structured_fields(): void
    {
        $taf = new Taf(['wind_speed_kt' => 18, 'wind_gust_kt' => 30]);
        $this->assertTrue($taf->windAtAbove(15));
        $this->assertTrue($taf->windGusts());

        $calm = new Taf(['wind_speed_kt' => 5, 'wind_gust_kt' => null]);
        $this->assertFalse($calm->windAtAbove(15));
        $this->assertNull($calm->windGusts());

        $unknown = new Taf(['wind_speed_kt' => null]);
        $this->assertFalse($unknown->windAtAbove(15));
    }

    public function test_visibility_parses_statute_miles_and_unbounded_suffix(): void
    {
        // 2 statute miles ≈ 3219 meters
        $taf = new Taf(['visibility_statute_mi' => '2']);
        $this->assertTrue($taf->sightBelow(5000));
        $this->assertFalse($taf->sightAtAbove(5000));

        // 6+ means "at or above 6" — it can never assert visibility below a higher bound
        $unbounded = new Taf(['visibility_statute_mi' => '6+']);
        $this->assertTrue($unbounded->sightAtAbove(5000));
        $this->assertFalse($unbounded->sightBelow(99999));

        $missing = new Taf(['visibility_statute_mi' => null]);
        $this->assertFalse($missing->sightAtAbove(5000));
        $this->assertFalse($missing->sightBelow(5000));
    }

    public function test_ceiling_reads_sky_condition_layers(): void
    {
        $taf = new Taf(['sky_condition' => [
            ['cover' => 'FEW', 'base_ft_agl' => 200],
            ['cover' => 'BKN', 'base_ft_agl' => 250],
        ]]);
        $this->assertTrue($taf->ceilingAtAbove(300));

        $high = new Taf(['sky_condition' => [['cover' => 'OVC', 'base_ft_agl' => 5000]]]);
        $this->assertFalse($high->ceilingAtAbove(300));

        // OVX is the cache XML's obscured-sky cover value
        $obscured = new Taf(['sky_condition' => [['cover' => 'OVX', 'base_ft_agl' => 100]]]);
        $this->assertTrue($obscured->ceilingAtAbove(300));

        $none = new Taf(['sky_condition' => null]);
        $this->assertFalse($none->ceilingAtAbove(300));
    }

    public function test_weather_phenomena_match_against_wx_string(): void
    {
        $this->assertTrue((new Taf(['wx_string' => 'FG']))->foggy());
        $this->assertTrue((new Taf(['wx_string' => '+SHRA']))->heavyRain());
        $this->assertTrue((new Taf(['wx_string' => '+SN']))->heavySnow());
        $this->assertTrue((new Taf(['wx_string' => '+TSRA BR']))->thunderstorm());

        $clear = new Taf(['wx_string' => null]);
        $this->assertFalse($clear->foggy());
        $this->assertFalse($clear->heavyRain());
        $this->assertFalse($clear->heavySnow());
        $this->assertFalse($clear->thunderstorm());
    }

    public function test_scoreable_periods(): void
    {
        // Base period, FM and BECMG are always scored
        $this->assertTrue((new Taf(['change_indicator' => null]))->isScoreable());
        $this->assertTrue((new Taf(['change_indicator' => 'FM']))->isScoreable());
        $this->assertTrue((new Taf(['change_indicator' => 'BECMG']))->isScoreable());

        // Both PROB shapes are scored — a bare PROBxx group and a combined PROBxx TEMPO
        $this->assertTrue((new Taf(['change_indicator' => 'PROB', 'probability' => 30]))->isScoreable());
        $this->assertTrue((new Taf(['change_indicator' => 'TEMPO', 'probability' => 40]))->isScoreable());

        // A bare TEMPO has no percentage to show and was never asserted for its full window
        $this->assertFalse((new Taf(['change_indicator' => 'TEMPO', 'probability' => null]))->isScoreable());
    }
}
