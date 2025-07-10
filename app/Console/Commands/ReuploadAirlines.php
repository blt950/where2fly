<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Airline;

class ReuploadAirlines extends Command
{
    protected $signature = 'airlines:reupload {jsonFile}';
    protected $description = 'Load airlines from a JSON file and insert new records if not present';

    public function handle()
    {
        $jsonFile = $this->argument('jsonFile');

        if (!file_exists($jsonFile)) {
            $this->error("File not found: $jsonFile");
            return 1;
        }

        $json = file_get_contents($jsonFile);
        $airlines = json_decode($json, true);

        if (!is_array($airlines)) {
            $this->error("Invalid JSON format.");
            return 1;
        }

        foreach ($airlines as $record) {
            if (
                !empty($record['name']) &&
                !empty($record['iata_code']) &&
                !empty($record['icao_code'])
            ) {
                $exists = Airline::where('icao_code', $record['icao_code'])->exists();

                if (!$exists) {
                    $airline = new Airline();
                    $airline->name = $record['name'];
                    $airline->iata_code = $record['iata_code'];
                    $airline->icao_code = $record['icao_code'];
                    $airline->save();

                    $this->info("Added airline: {$record['name']} ({$record['iata_code']}, {$record['icao_code']})");
                }
            }
        }

        $this->info('Reupload complete.');
    }
}