<?php

namespace Tests\Unit;

use App\Models\AirportScore;
use PHPUnit\Framework\TestCase;

class ForecastWeightTest extends TestCase
{
    public function test_certain_periods_weigh_full_and_uncertainty_steps_down(): void
    {
        // FM/BECMG periods (and every non-TAF source) are firm predictions
        $this->assertSame(1.0, AirportScore::forecastWeight(null, false));

        // TEMPO above PROB40 above PROB30, combined PROBnn TEMPO below the bare PROB
        $this->assertSame(0.7, AirportScore::forecastWeight(null, true));
        $this->assertSame(0.5, AirportScore::forecastWeight(40, false));
        $this->assertSame(0.4, AirportScore::forecastWeight(40, true));
        $this->assertSame(0.3, AirportScore::forecastWeight(30, false));
        $this->assertSame(0.25, AirportScore::forecastWeight(30, true));
    }

    public function test_oddball_probabilities_fall_back_to_the_literal_fraction(): void
    {
        // TAFs only carry PROB30/PROB40, but a nonstandard value shouldn't crash
        $this->assertSame(0.5, AirportScore::forecastWeight(50, false));
        $this->assertSame(0.2, AirportScore::forecastWeight(20, true));
    }
}
