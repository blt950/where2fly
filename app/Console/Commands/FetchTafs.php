<?php

namespace App\Console\Commands;

use App\Helpers\AviationWeatherHelper;
use App\Helpers\WeatherScoreHelper;
use App\Models\Airport;
use App\Models\AirportScore;
use App\Models\Taf;
use App\Models\TafForecast;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FetchTafs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:tafs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch all current TAFs from the aviationweather.gov bulk cache and score their forecast periods';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $processTime = microtime(true);
        $this->info("Starting fetching of TAF's");

        $paths = AviationWeatherHelper::downloadCache('https://aviationweather.gov/data/cache/tafs.cache.xml.gz');
        $tafDocuments = $this->parseTafDocuments($paths['xml']);

        $airports = Airport::whereIn('icao', array_keys($tafDocuments))->get()->keyBy(fn ($airport) => strtoupper($airport->icao));

        // Only reprocess airports whose TAF has actually been reissued/amended since the last run. TAFs only change every ~6 hours plus occasional amendments
        $storedIssues = Taf::pluck('issued_at', 'airport_id');

        $changedDocuments = [];
        foreach ($tafDocuments as $icao => $document) {
            $airport = $airports[$icao] ?? null;
            if (! $airport) {
                continue;
            }

            if (isset($storedIssues[$airport->id]) && Carbon::parse($storedIssues[$airport->id])->gte($document['issued_at'])) {
                continue;
            }

            $changedDocuments[$airport->id] = $document;
        }

        $forecastInsert = [];
        $airportScoreInsert = [];

        // Cleanup the old TAFs and their periods
        foreach (array_chunk(array_keys($changedDocuments), 500) as $chunk) {
            Taf::whereIn('airport_id', $chunk)->delete();
            AirportScore::whereIn('airport_id', $chunk)->where('source', AirportScore::SOURCE_TAF)->delete();
        }

        // Insert fresh TAF documents and their periods
        foreach ($changedDocuments as $airportId => $document) {
            $tafId = DB::table('tafs')->insertGetId([
                'airport_id' => $airportId,
                'raw_text' => $document['raw_text'],
                'issued_at' => $document['issued_at'],
                'bulletin_time' => $document['bulletin_time'],
                'valid_from' => $document['valid_from'],
                'valid_to' => $document['valid_to'],
                'last_update' => now(),
            ]);

            foreach ($document['periods'] as $period) {
                $forecastInsert[] = array_merge($period, ['taf_id' => $tafId]);

                // TEMPO/PROB periods score like any other, flagged so the icon
                // carries the uncertainty badge — with a percentage when given
                $data = [];
                if ($period['probability'] !== null) {
                    $data['probability'] = $period['probability'];
                }
                if ($period['change_indicator'] === 'TEMPO') {
                    $data['tempo'] = true;
                }
                $data = $data ? json_encode($data) : null;

                foreach (WeatherScoreHelper::reasons(new TafForecast($period)) as $reason) {
                    $airportScoreInsert[] = [
                        'airport_id' => $airportId,
                        'reason' => $reason,
                        'score' => 1,
                        'data' => $data,
                        'source' => AirportScore::SOURCE_TAF,
                        'valid_from' => $period['valid_from'],
                        'valid_to' => $period['valid_to'],
                    ];
                }
            }
        }

        foreach (array_chunk($forecastInsert, 500) as $chunk) {
            TafForecast::insert($chunk);
        }

        foreach (array_chunk($airportScoreInsert, 500) as $chunk) {
            AirportScore::insert($chunk);
        }

        // Prune what has fully passed, expired forecasts can never cover an ETA
        TafForecast::where('valid_to', '<', now())->delete();
        Taf::where('valid_to', '<', now())->delete();
        AirportScore::where('source', AirportScore::SOURCE_TAF)->where('valid_to', '<', now())->delete();

        AviationWeatherHelper::cleanup($paths);

        $this->info('Fetching of ' . count($tafDocuments) . " TAF's (" . count($changedDocuments) . ' changed) finished in ' . round(microtime(true) - $processTime) . ' seconds');

    }

    /**
     * Parse the cache XML into one document per station, keeping only the newest
     * issue per station and each document's forecast periods as column-ready arrays.
     */
    private function parseTafDocuments(string $xmlPath): array
    {
        $tafDocuments = [];
        $xml = simplexml_load_file($xmlPath);

        foreach ($xml->data->TAF as $taf) {
            $icao = strtoupper((string) $taf->station_id);
            if ($icao === '' || ! isset($taf->raw_text, $taf->issue_time)) {
                continue;
            }

            // issue_time advances on amendments (TAF AMD) while bulletin_time doesn't,
            // which is what makes the issued_at change-detection catch amendments
            $issuedAt = Carbon::parse((string) $taf->issue_time);
            if (isset($tafDocuments[$icao]) && $tafDocuments[$icao]['issued_at']->gte($issuedAt)) {
                continue;
            }

            $periods = [];
            foreach ($taf->forecast as $forecast) {
                if (! isset($forecast->fcst_time_from, $forecast->fcst_time_to)) {
                    continue;
                }

                // A period that has fully passed can never cover an ETA. Inserting it would only churn against the expiry pruning
                if (Carbon::parse((string) $forecast->fcst_time_to)->isPast()) {
                    continue;
                }

                $skyCondition = [];
                foreach ($forecast->sky_condition as $layer) {
                    $skyCondition[] = [
                        'cover' => (string) $layer['sky_cover'],
                        'base_ft_agl' => isset($layer['cloud_base_ft_agl']) ? (int) $layer['cloud_base_ft_agl'] : null,
                    ];
                }

                $periods[] = [
                    'change_indicator' => isset($forecast->change_indicator) ? (string) $forecast->change_indicator : null,
                    'probability' => isset($forecast->probability) ? (int) $forecast->probability : null,
                    'wind_dir_degrees' => isset($forecast->wind_dir_degrees) ? (string) $forecast->wind_dir_degrees : null,
                    'wind_speed_kt' => isset($forecast->wind_speed_kt) ? (int) $forecast->wind_speed_kt : null,
                    'wind_gust_kt' => isset($forecast->wind_gust_kt) ? (int) $forecast->wind_gust_kt : null,
                    'visibility_statute_mi' => isset($forecast->visibility_statute_mi) ? (string) $forecast->visibility_statute_mi : null,
                    'wx_string' => isset($forecast->wx_string) ? (string) $forecast->wx_string : null,
                    'ceiling_ft_agl' => TafForecast::ceilingFromSkyCondition($skyCondition),
                    'valid_from' => Carbon::parse((string) $forecast->fcst_time_from),
                    'valid_to' => Carbon::parse((string) $forecast->fcst_time_to),
                ];
            }

            if (! count($periods)) {
                continue;
            }

            $tafDocuments[$icao] = [
                'issued_at' => $issuedAt,
                'bulletin_time' => isset($taf->bulletin_time) ? Carbon::parse((string) $taf->bulletin_time) : null,
                'raw_text' => preg_replace('/^TAF (?:AMD |COR )?' . preg_quote($icao, '/') . ' /', '', (string) $taf->raw_text),
                'valid_from' => isset($taf->valid_time_from) ? Carbon::parse((string) $taf->valid_time_from) : collect($periods)->min('valid_from'),
                'valid_to' => isset($taf->valid_time_to) ? Carbon::parse((string) $taf->valid_time_to) : collect($periods)->max('valid_to'),
                'periods' => $periods,
            ];
        }

        return $tafDocuments;
    }
}
