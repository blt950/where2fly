<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Airline;

class CalcFlights extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calc:flights';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate amount of flights per airline';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        // Set has_flights to true for airlines that have flights and opposite for airlines that don't
        Airline::has('flights')->update(['has_flights' => true]);
        Airline::doesntHave('flights')->update(['has_flights' => false]);

        return Command::SUCCESS;
    }
}
