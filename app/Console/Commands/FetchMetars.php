<?php

namespace App\Console\Commands;

use App\Models\Airport;
use App\Models\Metar;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

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
    protected $description = 'Fetch all latest METARs from VATSIM';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $processTime = microtime(true);
        $this->info("Starting fetching of METAR's");

        $response = Http::timeout(60)->retry(3, 1000)->get('https://metar.vatsim.net?id=all');
        if ($response->successful()) {
            $data = collect(preg_split("/\r\n|\n|\r/", $response->body()));

            // Index METAR lines by ICAO code
            $airportsData = $data->keyBy(fn ($d) => substr($d, 0, 4));

            // Get the relevant airports
            $upsertData = [];
            foreach (Airport::whereIn('icao', $airportsData->keys()->all())->get() as $airport) {
                $d = $airportsData[strtoupper($airport->icao)];

                // Reset temperature to avoid carrying over a value from a previous iteration
                unset($temperature);

                // Don't add the METAR if it's not from today
                $metarDate = (int) substr($d, 5, 2);
                if ($metarDate != date('d')) {
                    continue;
                }

                $time = now()->setDay($metarDate)->setHour((int) substr($d, 7, 2))->setMinute((int) substr($d, 9, 2))->setSeconds(0);
                $metar = substr($d, 13, null);

                // Fetch the wind direction and speed
                $windData = ['direction' => null, 'speed' => null, 'gusting' => null];
                $windResult = [];
                if (preg_match('/(\d\d\d)(\d\d)G?(\d\d)?KT/', $metar, $windResult)) {
                    $windData['direction'] = $windResult[1];
                    $windData['speed'] = $windResult[2];
                    if (isset($windResult[3])) {
                        $windData['gusting'] = $windResult[3];
                    }
                } elseif (preg_match('/(\d\d\d)(\d\d)G?(\d\d)?MPS/', $metar, $windResult)) {
                    $windData['direction'] = $windResult[1];
                    $windData['speed'] = round($windResult[2] * 1.943844);
                    if (isset($windResult[3])) {
                        $windData['gusting'] = round($windResult[3] * 1.943844);
                    }
                }

                if (preg_match('/(M?\d\d)\/(M?\d\d)/', $metar, $temperatureResult)) {
                    $temperature = str_starts_with($temperatureResult[1], 'M')
                        ? -(int) substr($temperatureResult[1], 1)
                        : (int) $temperatureResult[1];
                }

                // Check for missing data
                if (! isset($temperature)) {
                    continue;
                }

                // Upsert the data
                $upsertData[] = [
                    'airport_id' => (int) $airport->id,
                    'last_update' => $time,
                    'metar' => $metar,
                    'wind_direction' => (int) $windData['direction'],
                    'wind_speed' => (int) $windData['speed'],
                    'wind_gusts' => (int) $windData['gusting'],
                    'temperature' => (int) $temperature,
                ];
            }

            // Update the data
            Metar::upsert(
                $upsertData,
                ['airport_id'],
                ['last_update', 'metar', 'wind_direction', 'wind_speed', 'wind_gusts', 'temperature']
            );

        }

        $this->info('Fetching of ' . $data->count() . " METAR's finished in " . round(microtime(true) - $processTime) . ' seconds');

    }
}
