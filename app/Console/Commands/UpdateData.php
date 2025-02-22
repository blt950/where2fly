<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class UpdateData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:data';

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
        $this->info('> Calling all relevant data update commands...');

        $this->info('>> fetch:metars running');
        Artisan::call('fetch:metars');

        $this->info('>> fetch:vatsim running');
        Artisan::call('fetch:vatsim');

        $this->info('>> calc:scores running');
        Artisan::call('calc:scores');

        $this->info('> Done with all commands in ' . round(microtime(true) - $processTime) . ' seconds!');

    }
}
