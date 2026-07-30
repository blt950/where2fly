<?php

namespace Tests;

use App\Models\Airport;
use Database\Seeders\TestAirportSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Run TestAirportSeeder once after migrate:fresh, before per-test transactions begin.
     * This means airports are committed to the DB and survive transaction rollbacks.
     */
    protected bool $seed = true;

    protected string $seeder = TestAirportSeeder::class;

    /**
     * Keyed by ICAO — available in every test after setUp().
     *
     * @var array<string, Airport>
     */
    protected array $airports = [];

    private const SEED_ICAOS = ['KLAX', 'KSFO', 'KJFK', 'KORD', 'EGLL', 'EDDM', 'EDDF', 'EHAM', 'LFPG', 'RJTT'];

    protected function setUp(): void
    {
        parent::setUp();

        if (! \Schema::hasTable('airports')) {
            return;
        }

        // Single query — seeder already committed these rows before our transaction.
        $this->airports = Airport::whereIn('icao', self::SEED_ICAOS)
            ->get()
            ->keyBy('icao')
            ->all();
    }
}
