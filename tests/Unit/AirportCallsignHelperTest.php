<?php

namespace Tests\Unit;

use App\Helpers\AirportCallsignHelper;
use PHPUnit\Framework\TestCase;

class AirportCallsignHelperTest extends TestCase
{
    public function test_long_prefixes_resolve_as_icao(): void
    {
        $this->assertSame('EGLL', AirportCallsignHelper::resolveIcao('EGLL_TWR'));
        $this->assertSame('EDDC', AirportCallsignHelper::resolveIcao('EDDC_GND'));
    }

    public function test_short_prefixes_use_australian_table_then_american_heuristic(): void
    {
        $this->assertSame('YSSY', AirportCallsignHelper::resolveIcao('SY_TWR'));
        $this->assertSame('KJFK', AirportCallsignHelper::resolveIcao('JFK_GND'));
    }

    public function test_non_airport_positions_are_skipped(): void
    {
        $this->assertNull(AirportCallsignHelper::resolveIcao('LON_CTR'));
        $this->assertNull(AirportCallsignHelper::resolveIcao('GANDER_FSS'));
        $this->assertNull(AirportCallsignHelper::resolveIcao('EGLL_ATIS'));
    }

    public function test_unparseable_callsigns_return_null(): void
    {
        $this->assertNull(AirportCallsignHelper::resolveIcao('_TWR'));
        $this->assertNull(AirportCallsignHelper::resolveIcao('NOSEPARATOR'));
    }
}
