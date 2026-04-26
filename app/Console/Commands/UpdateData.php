<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

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
        $this->call('fetch:metars');

        $this->info('>> fetch:vatsim running');
        $this->call('fetch:vatsim');

        $this->info('>> calc:scores running');
        $this->call('calc:scores');

        $this->info('> Done with all commands in ' . round(microtime(true) - $processTime) . ' seconds!');

    }
}
