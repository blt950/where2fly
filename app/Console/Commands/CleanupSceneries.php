<?php

namespace App\Console\Commands;

use App\Models\Scenery;
use Illuminate\Console\Command;

class CleanupSceneries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cleanup:sceneries';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get all Sceneries with no attached Simulators and delete them
        $sceneries = Scenery::doesntHave('simulators')->get();
        $this->info('Deleting ' . $sceneries->count() . ' sceneries');
        $sceneries->each->delete();
    }
}
