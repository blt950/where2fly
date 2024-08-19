<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Update data
        $schedule->command('update:data')->hourlyAt(15);
        $schedule->command('update:data')->hourlyAt(40);

        // Fetch flights
        $schedule->command('fetch:flights')->everyThirtyMinutes();

        // Update if airlines have flights
        $schedule->command('calc:flights')->daily();

        // Backups
        $schedule->command('backup:clean')->daily()->at('01:00');
        $schedule->command('backup:run')->daily()->at('01:30');

        // Clear expired password reset tokens
        $schedule->command('auth:clear-resets')->everyFifteenMinutes();

        // Fetch new disposable domains
        $schedule->command('disposable:update')->daily();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
