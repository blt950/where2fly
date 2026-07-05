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

        $matched = $this->matchedIds($eta);
        $this->assertContains($covering->id, $matched);
        $this->assertNotContains($ended->id, $matched);
    }

    public function test_online_controllers_show_in_now_views_and_predict_two_hours_from_logon(): void
    {
        // The live row covers "now" views for as long as the controller is online,
        // regardless of session length — but doesn't reach forecast ETAs
        $vatsimNow = $this->makeScore(['source' => AirportScore::SOURCE_VATSIM, 'reason' => 'VATSIM_ATC', 'valid_from' => now(), 'valid_to' => now()->addMinutes(30)]);
        $this->assertContains($vatsimNow->id, $this->matchedIds(now()));
        $this->assertNotContains($vatsimNow->id, $this->matchedIds(now()->addHours(8)));

        // An unbooked controller is predicted present strictly until logon+2h —
        // here they logged on 75 minutes ago, so the cutoff is 45 minutes out
        $freshLogon = $this->makeScore(['source' => AirportScore::SOURCE_LOGON_ESTIMATE, 'reason' => 'VATSIM_ATC', 'valid_from' => now(), 'valid_to' => now()->addMinutes(45)]);
        $this->assertContains($freshLogon->id, $this->matchedIds(now()->addMinutes(30)));
        $this->assertNotContains($freshLogon->id, $this->matchedIds(now()->addHour()));

        // A session already past 2h yields a window that can never match a forecast
        $longSession = $this->makeScore(['source' => AirportScore::SOURCE_LOGON_ESTIMATE, 'reason' => 'VATSIM_ATC', 'valid_from' => now(), 'valid_to' => now()->subHour()]);
        $this->assertNotContains($longSession->id, $this->matchedIds(now()->addMinutes(30)));
    }

    // -------------------------------------------------------------------------
    // Overlap sources (±1h tolerance)
    // -------------------------------------------------------------------------

    public function test_predicted_presence_matches_with_one_hour_overlap(): void
    {
        $eta = now()->addHours(5);

        $eventNear = $this->makeScore(['source' => AirportScore::SOURCE_EVENT, 'reason' => 'VATSIM_EVENT', 'valid_from' => $eta->copy()->addMinutes(45), 'valid_to' => $eta->copy()->addHours(3)]);
        $eventFar = $this->makeScore(['source' => AirportScore::SOURCE_EVENT, 'reason' => 'VATSIM_EVENT', 'valid_from' => $eta->copy()->addMinutes(90), 'valid_to' => $eta->copy()->addHours(4)]);

        // A booking starting shortly after the ETA still shows, so the pilot can
        // adjust their flight time to catch it
        $bookingNear = $this->makeScore(['source' => AirportScore::SOURCE_BOOKING, 'reason' => 'VATSIM_ATC', 'valid_from' => $eta->copy()->addMinutes(30), 'valid_to' => $eta->copy()->addHours(2)]);
        $bookingFar = $this->makeScore(['source' => AirportScore::SOURCE_BOOKING, 'reason' => 'VATSIM_ATC', 'valid_from' => $eta->copy()->addMinutes(90), 'valid_to' => $eta->copy()->addHours(3)]);

        $matched = $this->matchedIds($eta);
        $this->assertContains($eventNear->id, $matched);
        $this->assertNotContains($eventFar->id, $matched);
        $this->assertContains($bookingNear->id, $matched);
        $this->assertNotContains($bookingFar->id, $matched);
    }

    // -------------------------------------------------------------------------
    // Departure candidates: METAR-only weather
    // -------------------------------------------------------------------------

    public function test_metar_only_weather_ignores_tafs_and_always_trusts_the_metar(): void
    {
        $eta = now();

        $tafNow = $this->makeScore(['source' => AirportScore::SOURCE_TAF, 'valid_from' => now()->subHour(), 'valid_to' => now()->addHours(5)]);
        $expiredMetar = $this->makeScore(['source' => AirportScore::SOURCE_METAR, 'reason' => 'METAR_GUSTS', 'valid_from' => now()->subHours(2), 'valid_to' => now()->subHour()]);
        $this->makeTafPeriod(['change_indicator' => null, 'valid_from' => now()->subHour(), 'valid_to' => now()->addHours(5)]);

        $matched = AirportScore::where('airport_id', $this->airports['KJFK']->id)->coversEta($eta, true)->pluck('id')->all();

        // Even a TAF period covering right now is ignored, and the latest METAR
        // always counts — it's the weather truth where the pilot departs from
        $this->assertNotContains($tafNow->id, $matched);
        $this->assertContains($expiredMetar->id, $matched);

        // The PHP twin agrees
        $this->assertFalse($tafNow->coversEtaAt($eta, true, true));
        $this->assertTrue($expiredMetar->coversEtaAt($eta, true, true));
    }

    // -------------------------------------------------------------------------
    // METAR fallback
    // -------------------------------------------------------------------------

    public function test_metar_falls_back_when_no_taf_covers_eta(): void
    {
        $eta = now()->addHours(5);
        $expiredMetar = $this->makeScore(['source' => AirportScore::SOURCE_METAR, 'valid_from' => now()->subHour(), 'valid_to' => now()]);

        // No TAF at all: the expired METAR row still matches
        $this->assertContains($expiredMetar->id, $this->matchedIds($eta));

        // A period covering a different time doesn't switch the fallback off
        $this->makeTafPeriod(['change_indicator' => 'FM', 'valid_from' => $eta->copy()->addHours(2), 'valid_to' => $eta->copy()->addHours(4)]);
        $this->assertContains($expiredMetar->id, $this->matchedIds($eta));

        // Any period covering the ETA does — TEMPO included, they score too
        $this->makeTafPeriod(['change_indicator' => 'TEMPO', 'valid_from' => $eta->copy()->subHour(), 'valid_to' => $eta->copy()->addHour()]);
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
    // Display ordering and tooltips
    // -------------------------------------------------------------------------

    public function test_display_scores_serve_current_status_before_forecasts(): void
    {
        $airport = $this->airports['KJFK'];
        $window = ['valid_from' => now()->subHour(), 'valid_to' => now()->addHour()];

        $tafGusts = $this->makeScore(['source' => AirportScore::SOURCE_TAF, 'reason' => 'METAR_GUSTS'] + $window);
        $tafWindy = $this->makeScore(['source' => AirportScore::SOURCE_TAF, 'reason' => 'METAR_WINDY'] + $window);
        $metarWindy = $this->makeScore(['source' => AirportScore::SOURCE_METAR, 'reason' => 'METAR_WINDY'] + $window);
        $this->makeScore(['source' => AirportScore::SOURCE_VATSIM, 'reason' => 'VATSIM_ATC'] + $window);

        $airport->load('scores');
        $display = $airport->displayScores();

        // VATSIM signals first, then current METAR weather, TAF-only forecasts
        // last — and the duplicated WINDY reason is represented by its current METAR row
        $this->assertSame([AirportScore::SOURCE_VATSIM, AirportScore::SOURCE_METAR, AirportScore::SOURCE_TAF], $display->pluck('source')->all());
        $this->assertSame($metarWindy->id, $display->firstWhere('reason', 'METAR_WINDY')->id);
        $this->assertSame($tafGusts->id, $display->firstWhere('reason', 'METAR_GUSTS')->id);
    }

    public function test_popular_tooltip_shows_the_movement_count(): void
    {
        $popular = $this->makeScore(['source' => AirportScore::SOURCE_VATSIM, 'reason' => 'VATSIM_POPULAR', 'data' => ['movements' => 17], 'valid_from' => now(), 'valid_to' => now()->addMinutes(30)]);

        $this->assertSame('17 aircraft in vicinity', $popular->tooltipText());
    }

    // -------------------------------------------------------------------------
    // ATC icon helpers
    // -------------------------------------------------------------------------

    public function test_atc_booking_helpers_expose_facilities_in_order(): void
    {
        $airport = $this->airports['KJFK'];

        $this->makeScore(['source' => AirportScore::SOURCE_BOOKING, 'reason' => 'VATSIM_ATC', 'data' => ['callsign' => 'KJFK_APP', 'facility' => 'APP'], 'valid_from' => now(), 'valid_to' => now()->addHours(2)]);
        $this->makeScore(['source' => AirportScore::SOURCE_BOOKING, 'reason' => 'VATSIM_ATC', 'data' => ['callsign' => 'KJFK_DEL', 'facility' => 'DEL'], 'valid_from' => now()->addHour(), 'valid_to' => now()->addHours(3)]);
        $this->makeScore(['source' => AirportScore::SOURCE_VATSIM, 'reason' => 'VATSIM_ATC', 'data' => ['stations' => [['facility' => 'TWR', 'logon_time' => now()->subMinutes(80)->toIso8601String()]]], 'valid_from' => now(), 'valid_to' => now()->addMinutes(30)]);
        $logonEstimate = $this->makeScore(['source' => AirportScore::SOURCE_LOGON_ESTIMATE, 'reason' => 'VATSIM_ATC', 'data' => ['position' => 'KJFK_TWR', 'facility' => 'TWR', 'logon_time' => now()->subMinutes(80)->toIso8601String()], 'valid_from' => now(), 'valid_to' => now()->addMinutes(40)]);

        $airport->load('scores');

        $this->assertSame(['DEL', 'APP'], $airport->atcBookedFacilities()->all());
        $this->assertSame(['TWR'], $airport->atcOnlineFacilities()->all());
        $this->assertSame('TWR', $airport->atcOnlineStations()->first()['facility']);
        // The icon dots union online and booked facilities in ground-to-air order
        $this->assertSame(['DEL', 'TWR', 'APP'], $airport->atcFacilities()->all());
        $this->assertCount(2, $airport->atcBookingScores());
        $this->assertStringContainsString('APP ', $airport->atcBookingScores()->first()->tooltipText());
        // Online controllers show a relative logon time, never a logoff we don't know
        $this->assertSame('TWR online for 1h 20m', $logonEstimate->tooltipText());
    }
}
