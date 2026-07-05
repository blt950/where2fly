<?php

namespace App\Console\Commands;

use App\Helpers\AviationWeatherHelper;
use App\Models\Airport;
use App\Models\Metar;
use Carbon\Carbon;
use Illuminate\Console\Command;
use SimpleXMLElement;
use XMLReader;

class FetchMetars extends Command
{
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

        $this->info('Fetching of ' . count($airportsData) . " METAR's finished in " . round(microtime(true) - $processTime) . ' seconds');

    }
}
