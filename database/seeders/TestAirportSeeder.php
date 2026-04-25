<?php

namespace Database\Seeders;

use App\Models\Airport;
use Illuminate\Database\Seeder;
use MatanYadaev\EloquentSpatial\Enums\Srid;
use MatanYadaev\EloquentSpatial\Objects\Point;

class TestAirportSeeder extends Seeder
{
    public function run(): void
    {
        $airports = [
            ['icao' => 'KLAX', 'name' => 'Los Angeles International',   'lat' => 33.9425, 'lon' => -118.4081, 'type' => 'large_airport', 'iso_country' => 'US', 'iso_region' => 'US-CA', 'continent' => 'NA', 'municipality' => 'Los Angeles'],
            ['icao' => 'KSFO', 'name' => 'San Francisco International', 'lat' => 37.6189, 'lon' => -122.3750, 'type' => 'large_airport', 'iso_country' => 'US', 'iso_region' => 'US-CA', 'continent' => 'NA', 'municipality' => 'San Francisco'],
            ['icao' => 'KJFK', 'name' => 'John F Kennedy International', 'lat' => 40.6398, 'lon' => -73.7789, 'type' => 'large_airport', 'iso_country' => 'US', 'iso_region' => 'US-NY', 'continent' => 'NA', 'municipality' => 'New York'],
            ['icao' => 'KORD', 'name' => "Chicago O'Hare International", 'lat' => 41.9742, 'lon' => -87.9073, 'type' => 'large_airport', 'iso_country' => 'US', 'iso_region' => 'US-IL', 'continent' => 'NA', 'municipality' => 'Chicago'],
            ['icao' => 'EGLL', 'name' => 'London Heathrow',              'lat' => 51.4775, 'lon' => -0.4614, 'type' => 'large_airport', 'iso_country' => 'GB', 'iso_region' => 'GB-ENG', 'continent' => 'EU', 'municipality' => 'London'],
            ['icao' => 'EDDM', 'name' => 'Munich Airport',               'lat' => 48.3538, 'lon' => 11.7861, 'type' => 'large_airport', 'iso_country' => 'DE', 'iso_region' => 'DE-BY',  'continent' => 'EU', 'municipality' => 'Munich'],
            ['icao' => 'EDDF', 'name' => 'Frankfurt Airport',            'lat' => 50.0333, 'lon' => 8.5706, 'type' => 'large_airport', 'iso_country' => 'DE', 'iso_region' => 'DE-HE',  'continent' => 'EU', 'municipality' => 'Frankfurt'],
            ['icao' => 'EHAM', 'name' => 'Amsterdam Schiphol',           'lat' => 52.3086, 'lon' => 4.7639, 'type' => 'large_airport', 'iso_country' => 'NL', 'iso_region' => 'NL-NH',  'continent' => 'EU', 'municipality' => 'Amsterdam'],
            ['icao' => 'LFPG', 'name' => 'Paris Charles de Gaulle',      'lat' => 49.0128, 'lon' => 2.5500, 'type' => 'large_airport', 'iso_country' => 'FR', 'iso_region' => 'FR-IDF', 'continent' => 'EU', 'municipality' => 'Paris'],
            ['icao' => 'RJTT', 'name' => 'Tokyo Haneda',                 'lat' => 35.5494, 'lon' => 139.7798, 'type' => 'large_airport', 'iso_country' => 'JP', 'iso_region' => 'JP-13',  'continent' => 'AS', 'municipality' => 'Tokyo'],
        ];

        foreach ($airports as $data) {
            Airport::create([
                'icao' => $data['icao'],
                'local_code' => $data['icao'],
                'name' => $data['name'],
                'type' => $data['type'],
                'latitude_deg' => $data['lat'],
                'longitude_deg' => $data['lon'],
                'continent' => $data['continent'],
                'iso_country' => $data['iso_country'],
                'iso_region' => $data['iso_region'],
                'municipality' => $data['municipality'],
                'elevation_ft' => 100,
                'scheduled_service' => 'yes',
                'coordinates' => new Point($data['lat'], $data['lon'], Srid::WGS84->value),
            ]);
        }
    }
}
