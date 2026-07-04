<?php

namespace Tests\Unit;

use App\Models\TafForecast;
use PHPUnit\Framework\TestCase;

class TafForecastTest extends TestCase
{
    public function test_wind_methods_read_structured_fields(): void
    {
        $forecast = new TafForecast(['wind_speed_kt' => 18, 'wind_gust_kt' => 30]);
        $this->assertTrue($forecast->windAtAbove(15));
        $this->assertTrue($forecast->windGusts());

        $calm = new TafForecast(['wind_speed_kt' => 5, 'wind_gust_kt' => null]);
        $this->assertFalse($calm->windAtAbove(15));
        $this->assertNull($calm->windGusts());

        $unknown = new TafForecast(['wind_speed_kt' => null]);
        $this->assertFalse($unknown->windAtAbove(15));
    }

    public function test_visibility_parses_statute_miles_and_unbounded_suffix(): void
    {
        // 2 statute miles ≈ 3219 meters
        $forecast = new TafForecast(['visibility_statute_mi' => '2']);
        $this->assertTrue($forecast->sightBelow(5000));
        $this->assertFalse($forecast->sightAtAbove(5000));

        // 6+ means "at or above 6" — it can never assert visibility below a higher bound
        $unbounded = new TafForecast(['visibility_statute_mi' => '6+']);
        $this->assertTrue($unbounded->sightAtAbove(5000));
        $this->assertFalse($unbounded->sightBelow(99999));

        $missing = new TafForecast(['visibility_statute_mi' => null]);
        $this->assertFalse($missing->sightAtAbove(5000));
        $this->assertFalse($missing->sightBelow(5000));
    }

    public function test_ceiling_derived_from_sky_condition_layers(): void
    {
        // FEW isn't a ceiling; the lowest qualifying layer wins
        $this->assertSame(250, TafForecast::ceilingFromSkyCondition([
            ['cover' => 'FEW', 'base_ft_agl' => 200],
            ['cover' => 'BKN', 'base_ft_agl' => 250],
            ['cover' => 'OVC', 'base_ft_agl' => 5000],
        ]));

        // OVX is the cache XML's obscured-sky cover value
        $this->assertSame(100, TafForecast::ceilingFromSkyCondition([['cover' => 'OVX', 'base_ft_agl' => 100]]));
        $this->assertNull(TafForecast::ceilingFromSkyCondition([['cover' => 'SCT', 'base_ft_agl' => 1000]]));
        $this->assertNull(TafForecast::ceilingFromSkyCondition([]));
    }

    public function test_ceiling_at_above_compares_the_stored_ceiling(): void
    {
        $this->assertTrue((new TafForecast(['ceiling_ft_agl' => 250]))->ceilingAtAbove(300));
        $this->assertFalse((new TafForecast(['ceiling_ft_agl' => 5000]))->ceilingAtAbove(300));
        $this->assertFalse((new TafForecast(['ceiling_ft_agl' => null]))->ceilingAtAbove(300));
    }

    public function test_weather_phenomena_match_against_wx_string(): void
    {
        $this->assertTrue((new TafForecast(['wx_string' => 'FG']))->foggy());
        $this->assertTrue((new TafForecast(['wx_string' => '+SHRA']))->heavyRain());
        $this->assertTrue((new TafForecast(['wx_string' => '+SN']))->heavySnow());
        $this->assertTrue((new TafForecast(['wx_string' => '+TSRA BR']))->thunderstorm());

        $clear = new TafForecast(['wx_string' => null]);
        $this->assertFalse($clear->foggy());
        $this->assertFalse($clear->heavyRain());
        $this->assertFalse($clear->heavySnow());
        $this->assertFalse($clear->thunderstorm());
    }

    public function test_scoreable_periods(): void
    {
        // Base period, FM and BECMG are always scored
        $this->assertTrue((new TafForecast(['change_indicator' => null]))->isScoreable());
        $this->assertTrue((new TafForecast(['change_indicator' => 'FM']))->isScoreable());
        $this->assertTrue((new TafForecast(['change_indicator' => 'BECMG']))->isScoreable());

        // Both PROB shapes are scored — a bare PROBxx group and a combined PROBxx TEMPO
        $this->assertTrue((new TafForecast(['change_indicator' => 'PROB', 'probability' => 30]))->isScoreable());
        $this->assertTrue((new TafForecast(['change_indicator' => 'TEMPO', 'probability' => 40]))->isScoreable());

        // A bare TEMPO has no percentage to show and was never asserted for its full window
        $this->assertFalse((new TafForecast(['change_indicator' => 'TEMPO', 'probability' => null]))->isScoreable());
    }
}
