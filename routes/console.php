<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Update METARs, TAFs, events, online controllers and calculate new scores.
Schedule::command('update:data')->hourlyAt(5);
Schedule::command('update:data')->hourlyAt(35);

// Fetch ATC bookings
Schedule::command('fetch:bookings')->everyThirtyMinutes();

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
