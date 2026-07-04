<?php

namespace Tests\Feature;

use App\Models\AirportScore;
use App\Models\Taf;
use App\Models\TafForecast;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScorePredictionTest extends TestCase
{
    use RefreshDatabase;

    private function makeScore(array $attributes): AirportScore
    {
        return AirportScore::create(array_merge([
            'airport_id' => $this->airports['KJFK']->id,
            'reason' => 'METAR_WINDY',
            'score' => 1,
            'data' => null,
        ], $attributes));
    }

    private function makeTafPeriod(array $attributes): TafForecast
    {
        $taf = Taf::firstOrCreate(
            ['airport_id' => $this->airports['KJFK']->id],
            [
                'raw_text' => 'TAF KJFK 010000Z 0100/0206 27015G25KT P6SM SCT035',
                'issued_at' => now(),
                'valid_from' => now(),
                'valid_to' => now()->addHours(30),
                'last_update' => now(),
            ]
        );

        return TafForecast::create(array_merge(['taf_id' => $taf->id], $attributes));
    }

    private function matchedIds($eta): array
    {
        return AirportScore::where('airport_id', $this->airports['KJFK']->id)
            ->coversEta($eta)
            ->pluck('id')
            ->all();
    }

    // -------------------------------------------------------------------------
    // Exact containment sources
    // -------------------------------------------------------------------------

    public function test_exact_sources_match_only_when_window_contains_eta(): void
    {
        $eta = now()->addHours(5);

        $covering = $this->makeScore(['source' => AirportScore::SOURCE_TAF, 'valid_from' => $eta->copy()->subHour(), 'valid_to' => $eta->copy()->addHour()]);
        $ended = $this->makeScore(['source' => AirportScore::SOURCE_TAF, 'valid_from' => now(), 'valid_to' => $eta->copy()->subHours(2)]);

        // Bookings match exactly — no padding, even when the window starts 30 minutes after ETA
        $bookingNear = $this->makeScore(['source' => AirportScore::SOURCE_BOOKING, 'reason' => 'VATSIM_ATC', 'valid_from' => $eta->copy()->addMinutes(30), 'valid_to' => $eta->copy()->addHours(2)]);

        // A live VATSIM row only covers until the next poll, never a 5h-out ETA
        $vatsimNow = $this->makeScore(['source' => AirportScore::SOURCE_VATSIM, 'reason' => 'VATSIM_ATC', 'valid_from' => now(), 'valid_to' => now()->addMinutes(30)]);

        $matched = $this->matchedIds($eta);
        $this->assertContains($covering->id, $matched);
        $this->assertNotContains($ended->id, $matched);
        $this->assertNotContains($bookingNear->id, $matched);
        $this->assertNotContains($vatsimNow->id, $matched);
    }

    // -------------------------------------------------------------------------
    // Overlap sources (±1h tolerance)
    // -------------------------------------------------------------------------

    public function test_inexact_sources_match_with_one_hour_overlap(): void
    {
        $eta = now()->addHours(5);

        $eventNear = $this->makeScore(['source' => AirportScore::SOURCE_EVENT, 'reason' => 'VATSIM_ATC', 'valid_from' => $eta->copy()->addMinutes(45), 'valid_to' => $eta->copy()->addHours(3)]);
        $eventFar = $this->makeScore(['source' => AirportScore::SOURCE_EVENT, 'reason' => 'VATSIM_ATC', 'valid_from' => $eta->copy()->addMinutes(90), 'valid_to' => $eta->copy()->addHours(4)]);
        $logonNear = $this->makeScore(['source' => AirportScore::SOURCE_LOGON_ESTIMATE, 'reason' => 'VATSIM_ATC', 'valid_from' => now(), 'valid_to' => $eta->copy()->subMinutes(45)]);

        $matched = $this->matchedIds($eta);
        $this->assertContains($eventNear->id, $matched);
        $this->assertNotContains($eventFar->id, $matched);
        $this->assertContains($logonNear->id, $matched);
    }

    // -------------------------------------------------------------------------
    // METAR fallback
    // -------------------------------------------------------------------------

    public function test_metar_falls_back_when_no_scoreable_taf_covers_eta(): void
    {
        $eta = now()->addHours(5);
        $expiredMetar = $this->makeScore(['source' => AirportScore::SOURCE_METAR, 'valid_from' => now()->subHour(), 'valid_to' => now()]);

        // No TAF at all: the expired METAR row still matches
        $this->assertContains($expiredMetar->id, $this->matchedIds($eta));

        // A bare TEMPO period covering the ETA isn't scoreable — fallback still applies
        $this->makeTafPeriod(['change_indicator' => 'TEMPO', 'valid_from' => $eta->copy()->subHour(), 'valid_to' => $eta->copy()->addHour()]);
        $this->assertContains($expiredMetar->id, $this->matchedIds($eta));

        // A scoreable period covering the ETA switches the METAR fallback off
        $this->makeTafPeriod(['change_indicator' => 'FM', 'valid_from' => $eta->copy()->subHour(), 'valid_to' => $eta->copy()->addHour()]);
        $this->assertNotContains($expiredMetar->id, $this->matchedIds($eta));
    }

    // -------------------------------------------------------------------------
    // PHP-side parity (loaded collections)
    // -------------------------------------------------------------------------

    public function test_scores_at_eta_mirrors_the_query_matching(): void
    {
        $eta = now()->addHours(5);
        $airport = $this->airports['KJFK'];

        $expiredMetar = $this->makeScore(['source' => AirportScore::SOURCE_METAR, 'valid_from' => now()->subHour(), 'valid_to' => now()]);
        $coveringTafScore = $this->makeScore(['source' => AirportScore::SOURCE_TAF, 'reason' => 'METAR_GUSTS', 'valid_from' => $eta->copy()->subHour(), 'valid_to' => $eta->copy()->addHour()]);
        $this->makeTafPeriod(['change_indicator' => null, 'valid_from' => $eta->copy()->subHour(), 'valid_to' => $eta->copy()->addHour()]);

        $airport->load('scores', 'taf.forecasts');
        [$scores, $hasTafAtEta] = $airport->scoresAtEta($eta);

        $this->assertTrue($hasTafAtEta);
        $this->assertContains($coveringTafScore->id, $scores->pluck('id')->all());
        $this->assertNotContains($expiredMetar->id, $scores->pluck('id')->all());
    }

    // -------------------------------------------------------------------------
    // ATC icon helpers
    // -------------------------------------------------------------------------

    public function test_atc_booking_helpers_expose_facilities_in_order(): void
    {
        $airport = $this->airports['KJFK'];

        $this->makeScore(['source' => AirportScore::SOURCE_BOOKING, 'reason' => 'VATSIM_ATC', 'data' => ['callsign' => 'KJFK_APP', 'facility' => 'APP'], 'valid_from' => now(), 'valid_to' => now()->addHours(2)]);
        $this->makeScore(['source' => AirportScore::SOURCE_BOOKING, 'reason' => 'VATSIM_ATC', 'data' => ['callsign' => 'KJFK_DEL', 'facility' => 'DEL'], 'valid_from' => now()->addHour(), 'valid_to' => now()->addHours(3)]);
        $this->makeScore(['source' => AirportScore::SOURCE_VATSIM, 'reason' => 'VATSIM_ATC', 'data' => ['stations' => ['TWR']], 'valid_from' => now(), 'valid_to' => now()->addMinutes(30)]);

        $airport->load('scores');

        $this->assertSame(['DEL', 'APP'], $airport->atcBookedFacilities()->all());
        $this->assertCount(2, $airport->atcBookingScores());
        $this->assertStringContainsString('APP ', $airport->atcBookingScores()->first()->tooltipText());
    }
}
