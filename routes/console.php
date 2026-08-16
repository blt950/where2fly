<?php

use Illuminate\Support\Facades\Schedule;

// Update METARs, TAFs, events, online controllers and calculate new scores.
Schedule::command('update:data')->hourlyAt(5)->sentryMonitor();
Schedule::command('update:data')->hourlyAt(35)->sentryMonitor();

// Fetch flights
Schedule::command('fetch:flights')->everyThirtyMinutes()->sentryMonitor();

// Update if airlines have flights
Schedule::command('calc:flights')->daily()->sentryMonitor();

// Fetch Github Issues cache
Schedule::command('fetch:github')->everyTenMinutes()->sentryMonitor();

// Cleanup sceneries without attached simulators
Schedule::command('cleanup:sceneries')->daily()->sentryMonitor();

// Backups
Schedule::command('backup:clean')->daily()->at('01:00')->sentryMonitor();
Schedule::command('backup:run')->daily()->at('01:30')->sentryMonitor();

// Delete users who haven't verified their email address
Schedule::command('account:clear-unverified')->daily()->sentryMonitor();

// Clear expired password reset tokens
Schedule::command('auth:clear-resets')->everyFifteenMinutes()->sentryMonitor();

// Fetch new disposable domains
Schedule::command('disposable:update')->daily()->sentryMonitor();
