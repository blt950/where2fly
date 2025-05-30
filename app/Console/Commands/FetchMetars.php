<?php

namespace App\Console\Commands;

use App\Models\Airport;
use App\Models\Metar;
use Carbon\Carbon;
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

        $response = Http::get('https://metar.vatsim.net?id=all');
        if ($response->successful()) {
            $data = collect(preg_split("/\r\n|\n|\r/", $response->body()));

            // Fetch all airports
            $airportsFilter = [];
            $airportsData = [];
            foreach ($data as $d) {
                $icao = substr($d, 0, 4);
                $airportsFilter[] = $icao;
                $airportsData[$icao] = $d;
            }

            // Get the relevant airports
            $upsertData = [];
            foreach (Airport::whereIn('icao', $airportsFilter)->get() as $airport) {
                $d = $airportsData[strtoupper($airport->icao)];

                // Don't add the METAR if it's not from today
                $metarDate = (int) substr($d, 5, 2);
                if ($metarDate != date('d')) {
                    continue;
                }

                $time = Carbon::now()->setDay($metarDate)->setHour((int) substr($d, 7, 2))->setMinute((int) substr($d, 9, 2))->setSeconds(0);
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
                    if (substr($temperatureResult[1], 0, 1) == 'M') {
                        $temperature = (int) substr($temperatureResult[1], 1) * -1;
                    } else {
                        $temperature = (int) $temperatureResult[1];
                    }
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
