<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        
        /*
        // Update VATSIM data
        $schedule->command('fetch:vatsim')->everyFifteenMinutes();

        // Update METAR data
        $schedule->command('fetch:metar')->hourlyAt(05);
        $schedule->command('fetch:metar')->hourlyAt(25);

        // Calculate scores
        $schedule->command('calc:scores')->hourlyAt(15);
        $schedule->command('calc:scores')->hourlyAt(40);
        */
    
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
