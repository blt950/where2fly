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
            ['icao' => 'EDDS', 'name' => 'Stuttgart Airport',            'lat' => 48.69,   'lon' => 9.22,    'type' => 'large_airport',  'iso_country' => 'DE', 'iso_region' => 'DE-BW',  'continent' => 'EU', 'municipality' => 'Stuttgart', 'elevation_ft' => 1276],
            ['icao' => 'EDDB', 'name' => 'Berlin Brandenburg Airport',   'lat' => 52.36,   'lon' => 13.50,   'type' => 'large_airport',  'iso_country' => 'DE', 'iso_region' => 'DE-BR',  'continent' => 'EU', 'municipality' => 'Berlin',    'elevation_ft' => 157],
            ['icao' => 'ENBR', 'name' => 'Bergen Airport, Flesland',     'lat' => 60.29,   'lon' => 5.22,    'type' => 'large_airport',  'iso_country' => 'NO', 'iso_region' => 'NO-46',  'continent' => 'EU', 'municipality' => 'Bergen',    'elevation_ft' => 170],
            ['icao' => 'ENGM', 'name' => 'Oslo Airport, Gardermoen',     'lat' => 60.19,   'lon' => 11.10,   'type' => 'large_airport',  'iso_country' => 'NO', 'iso_region' => 'NO-32',  'continent' => 'EU', 'municipality' => 'Oslo',      'elevation_ft' => 681],
            ['icao' => 'ENSO', 'name' => 'Stord Airport, Sørstokken',    'lat' => 59.79,   'lon' => 5.34,    'type' => 'medium_airport', 'iso_country' => 'NO', 'iso_region' => 'NO-46',  'continent' => 'EU', 'municipality' => 'Leirvik',   'elevation_ft' => 160],
            ['icao' => 'ENTC', 'name' => 'Tromsø Airport, Langnes',      'lat' => 69.68,   'lon' => 18.92,   'type' => 'large_airport',  'iso_country' => 'NO', 'iso_region' => 'NO-55',  'continent' => 'EU', 'municipality' => 'Tromsø',    'elevation_ft' => 31],
            ['icao' => 'ENTO', 'name' => 'Sandefjord Airport, Torp',     'lat' => 59.19,   'lon' => 10.26,   'type' => 'medium_airport', 'iso_country' => 'NO', 'iso_region' => 'NO-39',  'continent' => 'EU', 'municipality' => 'Torp',      'elevation_ft' => 286],
            ['icao' => 'ENSG', 'name' => 'Sogndal Airport, Haukåsen',    'lat' => 61.16,   'lon' => 7.14,    'type' => 'small_airport',  'iso_country' => 'NO', 'iso_region' => 'NO-46',  'continent' => 'EU', 'municipality' => 'Sogndal',   'elevation_ft' => 1633],
            ['icao' => 'ENSD', 'name' => 'Sandane Airport, Anda',        'lat' => 61.83,   'lon' => 6.11,    'type' => 'small_airport',  'iso_country' => 'NO', 'iso_region' => 'NO-46',  'continent' => 'EU', 'municipality' => 'Sandane',   'elevation_ft' => 196],
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
                'elevation_ft' => $data['elevation_ft'] ?? 100,
                'scheduled_service' => 'yes',
                'coordinates' => new Point($data['lat'], $data['lon'], Srid::WGS84->value),
            ]);
        }
    }
}
