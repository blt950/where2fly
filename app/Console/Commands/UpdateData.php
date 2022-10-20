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

        $this->info("> Calling all relevant data update commands...");

        Artisan::call('fetch:metars');
        Artisan::call('fetch:vatsim');
        Artisan::call('calc:score');

        $this->info("> Done with all commands!");

    }
}


