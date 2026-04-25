<?php

namespace Database\Factories;

use App\Models\Airport;
use Illuminate\Database\Eloquent\Factories\Factory;
use MatanYadaev\EloquentSpatial\Enums\Srid;
use MatanYadaev\EloquentSpatial\Objects\Point;

/**
 * @extends Factory<Airport>
 */
class AirportFactory extends Factory
{
    protected $model = Airport::class;

    public function definition(): array
    {
        $lat = fake()->latitude();
        $lon = fake()->longitude();

        return [
            'icao' => strtoupper(fake()->unique()->lexify('K???')),
            'type' => 'medium_airport',
            'name' => fake()->city().' Airport',
            'latitude_deg' => $lat,
            'longitude_deg' => $lon,
            'elevation_ft' => fake()->numberBetween(0, 5000),
            'continent' => 'NA',
            'iso_country' => 'US',
            'iso_region' => 'US-CA',
            'municipality' => fake()->city(),
            'scheduled_service' => 'yes',
            'gps_code' => null,
            'iata_code' => null,
            'local_code' => null,
            'total_score' => null,
            'coordinates' => new Point($lat, $lon, Srid::WGS84->value),
        ];
    }
}
