<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use RuntimeException;

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

        // RefreshDatabase wipes whatever database this resolves to; a stale config cache once
        // pointed it at the dev database
        $database = $app['config']->get('database.connections.' . $app['config']->get('database.default') . '.database');

        if ($database !== 'where2fly_test') {
            throw new RuntimeException("Refusing to run tests against database [{$database}]; expected [where2fly_test].");
        }

        return $app;
    }
}
