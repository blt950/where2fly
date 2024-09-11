<?php

namespace App\Console\Commands;

use App\Models\Airport;
use Illuminate\Console\Command;

class EnrichAirports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'enrich:airports';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enrich the airports with additional data';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $processTime = microtime(true);
        $count = 0;

        $this->info('> Enriching airports with additional data...');

        // Create booleans of airports that has airline service
        $airlineQuery = Airport::where('scheduled_service', 'yes')->update(['w2f_scheduled_service' => true]);

        // Create booleans of airports that is an airforce base
        $airforceAirports = Airport::where('name', 'like', '% RAF %')
            ->orWhere('name', 'like', 'RAF %')
            ->orWhere('name', 'like', '%Air Base%')
            ->orWhere('name', 'like', '%airbase%')
            ->orWhere('name', 'like', '% AFB %')
            ->orWhere('name', 'like', '%airforce%')
            ->orWhere('name', 'like', '%air force%')
            ->orWhere('name', 'like', '%Army Airfield%')
            ->orWhere('name', 'like', '% RNAS %')
            ->orWhere('name', 'like', 'RNAS%')
            ->orWhere('name', 'like', '% RAAF %')
            ->orWhere('name', 'like', 'RAAF%')
            ->orWhere('icao', 'like', 'ET__')
            ->update(['w2f_airforcebase' => true]);

        // Transfer the gps_code to icao to make sure newest icao codes are used by default
        $gpsCodeToIcao = Airport::where('gps_code', '!=', '')
            ->whereColumn('gps_code', '!=', 'icao')
            ->update(['icao' => \DB::raw('gps_code')]);

        // Upsert the data
        $this->info('> Done with enriching airports in ' . round(microtime(true) - $processTime) . ' seconds!');

        return Command::SUCCESS;
    }
}
