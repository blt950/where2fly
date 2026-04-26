<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Update data
Schedule::command('update:data')->hourlyAt(15);
Schedule::command('update:data')->hourlyAt(40);

// Fetch flights
Schedule::command('fetch:flights')->everyThirtyMinutes();

// Update if airlines have flights
Schedule::command('calc:flights')->daily();

// Fetch Github Issues cache
Schedule::command('fetch:github')->everyTenMinutes();

// Cleanup sceneries without attached simulators
Schedule::command('cleanup:sceneries')->daily();

// Backups
Schedule::command('backup:clean')->daily()->at('01:00');
Schedule::command('backup:run')->daily()->at('01:30');

// Delete users who haven't verified their email address
Schedule::command('account:clear-unverified')->daily();

// Clear expired password reset tokens
Schedule::command('auth:clear-resets')->everyFifteenMinutes();

// Fetch new disposable domains
Schedule::command('disposable:update')->daily();
