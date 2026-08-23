<?php

namespace Tests\Feature;

use App\Models\AirportScore;
use App\Models\Taf;
use App\Models\TafForecast;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoversEtaParityTest extends TestCase
{
    use RefreshDatabase;

    private Carbon $base;

    /** Offsets (minutes from $base) for [valid_from, valid_to], straddling every containment/overlap boundary */
    private const WINDOW_OFFSETS = [
        [-300, -120], [-300, -61], [-300, -60], [-300, -16], [-300, -15],
        [-120, 0], [-60, 60], [0, 120], [59, 180], [60, 180], [61, 180], [0, -60],
    ];

    private const SOURCES = [
        AirportScore::SOURCE_METAR,
        AirportScore::SOURCE_TAF,
        AirportScore::SOURCE_VATSIM,
        AirportScore::SOURCE_LOGON_ESTIMATE,
        AirportScore::SOURCE_BOOKING,
        AirportScore::SOURCE_EVENT,
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // DATETIME columns are second-precision, so freezing to a whole second
        // makes boundary rows test the matching rule, not a microsecond
        // truncation difference between the SQL string binding and Carbon.
        Carbon::setTestNow(Carbon::now()->startOfSecond());
        $this->base = now()->addHours(5);

        $this->seedTafScenarios();
        $this->seedScoreMatrix();
    }

    private function seedTafScenarios(): void
    {
        // KJFK: no TAF at all — exercises the fallback with nothing to fall back from.

        // KLAX: a TAF period covering every evaluated ETA around $base.
        $this->makeTaf('KLAX', $this->base->copy()->subHour(), $this->base->copy()->addHour());

        // KSFO: a TAF period far from every evaluated ETA — fallback stays active there.
        $this->makeTaf('KSFO', $this->base->copy()->addHours(10), $this->base->copy()->addHours(12));
    }

    private function makeTaf(string $icao, Carbon $validFrom, Carbon $validTo): void
    {
        $airport = $this->airports[$icao];

        $taf = Taf::firstOrCreate(
            ['airport_id' => $airport->id],
            [
                'raw_text' => "TAF {$icao} 010000Z 0100/0206 27015G25KT P6SM SCT035",
                'issued_at' => now(),
                'valid_from' => now(),
                'valid_to' => now()->addHours(30),
                'last_update' => now(),
            ]
        );

        TafForecast::create([
            'taf_id' => $taf->id,
            'change_indicator' => null,
            'valid_from' => $validFrom,
            'valid_to' => $validTo,
        ]);
    }

    private function seedScoreMatrix(): void
    {
        foreach (['KJFK', 'KLAX', 'KSFO'] as $icao) {
            $airportId = $this->airports[$icao]->id;

            foreach (self::SOURCES as $source) {
                foreach (self::WINDOW_OFFSETS as [$fromOffset, $toOffset]) {
                    AirportScore::create([
                        'airport_id' => $airportId,
                        'reason' => 'METAR_WINDY',
                        'source' => $source,
                        'score' => 1,
                        'data' => null,
                        'valid_from' => $this->base->copy()->addMinutes($fromOffset),
                        'valid_to' => $this->base->copy()->addMinutes($toOffset),
                    ]);
                }
            }
        }
    }

    public function test_sql_scope_and_php_twin_match_the_same_rows(): void
    {
        $etas = [
            $this->base->copy(),
            $this->base->copy()->subMinutes(90),
            $this->base->copy()->addMinutes(90),
            now(),
        ];

        $anyMatched = false;
        $anyPartial = false;

        foreach (['KJFK', 'KLAX', 'KSFO'] as $icao) {
            $airport = $this->airports[$icao];
            $airport->load('scores', 'taf.forecasts');
            $fullIdSet = $airport->scores->pluck('id')->sort()->values()->all();

            foreach ($etas as $eta) {
                foreach ([false, true] as $metarOnlyWeather) {
                    $sqlIds = AirportScore::where('airport_id', $airport->id)
                        ->coversEta($eta, $metarOnlyWeather)
                        ->pluck('id')
                        ->sort()
                        ->values()
                        ->all();

                    [$phpScores] = $airport->scoresAtEta($eta, $metarOnlyWeather);
                    $phpIds = $phpScores->pluck('id')->sort()->values()->all();

                    $offsetMinutes = $eta->diffInMinutes($this->base, false);
                    $message = "airport={$icao} etaOffsetFromBaseMinutes={$offsetMinutes} metarOnlyWeather=" . ($metarOnlyWeather ? 'true' : 'false');
                    $this->assertSame($sqlIds, $phpIds, $message);

                    $anyMatched = $anyMatched || count($sqlIds) > 0;
                    $anyPartial = $anyPartial || ($sqlIds !== $fullIdSet);
                }
            }
        }

        // Guards against a regression where both sides trivially match everything
        // or nothing, which would make the id-set diff above meaningless.
        $this->assertTrue($anyMatched, 'expected at least one grid combination to match some rows');
        $this->assertTrue($anyPartial, 'expected at least one grid combination to differ from the full row set');
    }

    public function test_carbon_and_raw_sql_eta_forms_of_the_scope_agree(): void
    {
        $airport = $this->airports['KLAX'];

        foreach ([false, true] as $metarOnlyWeather) {
            $carbonIds = AirportScore::where('airport_id', $airport->id)
                ->coversEta($this->base, $metarOnlyWeather)
                ->pluck('id')->sort()->values()->all();

            // Search binds a per-candidate SQL ETA expression (forecastEtaSql),
            // so the raw-string branch must select like the Carbon branch.
            $rawSqlIds = AirportScore::where('airport_id', $airport->id)
                ->coversEta("'" . $this->base->toDateTimeString() . "'", $metarOnlyWeather)
                ->pluck('id')->sort()->values()->all();

            $this->assertSame($carbonIds, $rawSqlIds, 'metarOnlyWeather=' . ($metarOnlyWeather ? 'true' : 'false'));
        }
    }
}
