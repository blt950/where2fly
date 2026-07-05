<?php

namespace App\Console\Commands;

use App\Helpers\AviationWeatherHelper;
use App\Helpers\WeatherScoreHelper;
use App\Models\Airport;
use App\Models\AirportScore;
use App\Models\Metar;
use Carbon\Carbon;
use Illuminate\Console\Command;
use SimpleXMLElement;
use XMLReader;

class FetchMetars extends Command
{
    /**
     * How long an observation stays valid — also the staleness cutoff for scoring.
     */
    private const METAR_VALIDITY_HOURS = 1;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:metars';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch all latest METARs from the aviationweather.gov bulk cache';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $processTime = microtime(true);
        $this->info("Starting fetching of METAR's");

        $paths = AviationWeatherHelper::downloadCache('https://aviationweather.gov/data/cache/metars.cache.xml.gz');

        // Stream-parse the METAR nodes — the file is too large to load as one DOM
        $airportsData = [];
        $reader = new XMLReader;
        $reader->open($paths['xml']);
        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->name !== 'METAR') {
                continue;
            }

            $node = new SimpleXMLElement($reader->readOuterXml());
            $icao = strtoupper((string) $node->station_id);
            if ($icao === '' || ! isset($node->raw_text, $node->observation_time)) {
                continue;
            }

            $observationTime = Carbon::parse((string) $node->observation_time);

            // A station can appear as both a routine METAR and a SPECI — keep the newest
            if (isset($airportsData[$icao]) && $airportsData[$icao]['last_update']->gte($observationTime)) {
                continue;
            }

            // Variable wind is reported as the literal string VRB — no usable direction
            $windDirection = null;
            if (isset($node->wind_dir_degrees) && is_numeric((string) $node->wind_dir_degrees)) {
                $windDirection = (int) $node->wind_dir_degrees;
            }

            $airportsData[$icao] = [
                'last_update' => $observationTime,
                'metar' => preg_replace('/^(?:METAR |SPECI )?' . preg_quote($icao, '/') . ' /', '', (string) $node->raw_text),
                'wind_direction' => $windDirection,
                'wind_speed' => (int) $node->wind_speed_kt,
                'wind_gusts' => (int) $node->wind_gust_kt,
                'temperature' => isset($node->temp_c) ? (int) round((float) $node->temp_c) : null,
            ];
        }
        $reader->close();

        // Get the relevant airports
        $upsertData = [];
        foreach (Airport::whereIn('icao', array_keys($airportsData))->get() as $airport) {
            $d = $airportsData[strtoupper($airport->icao)];

            // Check for missing data
            if ($d['temperature'] === null) {
                continue;
            }

            $upsertData[] = array_merge(['airport_id' => (int) $airport->id], $d);
        }

        // Update the data in chunks
        foreach (array_chunk($upsertData, 1000) as $chunk) {
            Metar::upsert(
                $chunk,
                ['airport_id'],
                ['last_update', 'metar', 'wind_direction', 'wind_speed', 'wind_gusts', 'temperature']
            );
        }

        AviationWeatherHelper::cleanup($paths);

        // Free the parsed cache before loading airport models — update:data runs
        // every command in one 256MB process
        $metarCount = count($airportsData);
        $airportsData = $upsertData = [];

        $this->scoreMetars();

        $this->info('Fetching and scoring of ' . $metarCount . " METAR's finished in " . round(microtime(true) - $processTime) . ' seconds');

    }

    /**
     * Rebuild the metar-sourced airport scores from the fresh observations.
     * Each fetch command owns its own airport_scores sources — this one owns `metar`.
     */
    private function scoreMetars(): void
    {
        $scoreInsert = [];
        foreach (Airport::where('type', '!=', 'closed')->has('metar')->with('metar', 'runways')->get() as $airport) {
            // A stale observation scores nothing — its old rows drop out on the rebuild
            if (now()->lte($airport->metar->last_update->copy()->addHours(self::METAR_VALIDITY_HOURS))) {
                $scoreInsert = array_merge($scoreInsert, $this->metarScores($airport));
            }
        }

        AirportScore::where('source', AirportScore::SOURCE_METAR)->delete();
        foreach (array_chunk($scoreInsert, 500) as $chunk) {
            AirportScore::insert($chunk);
        }
    }

    /**
     * Weather scores from the airport's current METAR, valid from the observation
     * until the next one is expected.
     *
     * @return array<array>
     */
    private function metarScores(Airport $airport): array
    {
        $scores = [];
        $metarArraySuffix = [
            'source' => AirportScore::SOURCE_METAR,
            'valid_from' => $airport->metar->last_update,
            'valid_to' => $airport->metar->last_update->copy()->addHours(self::METAR_VALIDITY_HOURS),
        ];

        // Fill in scores from current METAR observation
        foreach (WeatherScoreHelper::reasons($airport->metar) as $reason) {
            $scores[] = ['airport_id' => $airport->id, 'reason' => $reason, 'score' => 1, 'data' => null] + $metarArraySuffix;
        }

        $activeRunwayComponents = ['headwind' => 0, 'crosswind' => 0];
        $airportScoreRVRInserted = false;
        foreach ($airport->runways->where('closed', false) as $runway) {
            // Check RVR at runways
            if (
                $airportScoreRVRInserted == false &&
                ((! empty($runway->le_ident) && $airport->metar->rvrAtBelow($runway->le_ident, 700)) ||
                (! empty($runway->he_ident) && $airport->metar->rvrAtBelow($runway->he_ident, 700)))
            ) {
                $scores[] = ['airport_id' => $airport->id, 'reason' => 'METAR_RVR', 'score' => 1, 'data' => null] + $metarArraySuffix;
                $airportScoreRVRInserted = true;
            }

            // Calculate headwind component on active runway
            if (! empty($airport->metar->wind_direction)) {

                // Fallback to varchar runway identifier if heading is not present in data, which is common.
                if (empty($runway->le_heading) && ! empty($runway->le_ident)) {
                    $runway->le_heading = rwyIdentToHeading($runway->le_ident);
                }

                if (empty($runway->he_heading) && ! empty($runway->he_ident)) {
                    $runway->he_heading = rwyIdentToHeading($runway->he_ident);
                }

                // Set the components
                $headwindComponentLe = abs($airport->metar->wind_speed * cos(deg2rad($airport->metar->wind_direction - $runway->le_heading)));
                $crosswindComponentLe = abs($airport->metar->wind_speed * sin(deg2rad($airport->metar->wind_direction - $runway->le_heading)));

                $headwindComponentHe = abs($airport->metar->wind_speed * cos(deg2rad($airport->metar->wind_direction - $runway->he_heading)));
                $crosswindComponentHe = abs($airport->metar->wind_speed * sin(deg2rad($airport->metar->wind_direction - $runway->he_heading)));

                if ($activeRunwayComponents['headwind'] < $headwindComponentLe) {
                    $activeRunwayComponents['headwind'] = $headwindComponentLe;
                    $activeRunwayComponents['crosswind'] = $crosswindComponentLe;
                } elseif ($activeRunwayComponents['headwind'] < $headwindComponentHe) {
                    $activeRunwayComponents['headwind'] = $headwindComponentHe;
                    $activeRunwayComponents['crosswind'] = $crosswindComponentHe;
                }
            }
        }

        // Check if crosswind component is fun at active runway
        if ($airport->metar->wind_speed >= 15 && $activeRunwayComponents['crosswind'] > 12) {
            $scores[] = ['airport_id' => $airport->id, 'reason' => 'METAR_CROSSWIND', 'score' => 1, 'data' => null] + $metarArraySuffix;
        }

        return $scores;
    }
}
