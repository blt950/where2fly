<?php

namespace App\Console\Commands;

use App\Models\Airport;
use App\Models\Scenery;
use Illuminate\Console\Command;

class EnrichSceneries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'enrich:sceneries';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enrich the sceneries with filling data from the airports';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $processTime = microtime(true);

        $this->info('> Enriching sceneries with additional data...');
        $sceneries = Scenery::whereNull('airport_id')->get();

        foreach ($sceneries as $scenery) {
            $airport = Airport::where('icao', $scenery->icao)->first();
            if ($airport) {
                $scenery->airport_id = $airport->id;
                $scenery->save();
            }
        }

        // Upsert the data
        $this->info('> Done with enriching sceneries with airport id\'s in ' . round(microtime(true) - $processTime) . ' seconds!');

        return Command::SUCCESS;
    }
}
