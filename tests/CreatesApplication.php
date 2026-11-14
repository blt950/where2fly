<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return Application
     */
    public function createApplication()
    {
        $app = require __DIR__ . '/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // RefreshDatabase runs migrate:fresh — refuse to start against anything but the test database
        $database = $app['config']->get('database.connections.' . $app['config']->get('database.default') . '.database');
        if ($database !== 'where2fly_test' || ! $app->environment('testing')) {
            throw new \RuntimeException("Refusing to run tests against database '$database' (env: {$app->environment()}). Is a config cache overriding phpunit.xml?");
        }

        return $app;
    }
}
