<?php

namespace Database\Seeders;

use App\Models\Airport;
use App\Models\AirportScore;
use App\Models\Metar;
use App\Models\Runway;
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
            $airport = Airport::updateOrCreate(
                ['icao' => $data['icao']],
                [
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
                    'w2f_has_open_runway' => true,
                    'coordinates' => new Point($data['lat'], $data['lon'], Srid::WGS84->value),
                ]
            );

            Runway::firstOrCreate(
                ['airport_id' => $airport->id, 'le_ident' => '18'],
                [
                    'airport_ident' => $data['icao'],
                    'length_ft' => $data['rwy_length_ft'] ?? 9000,
                    'width_ft' => 150,
                    'surface' => 'ASP',
                    'lighted' => true,
                    'closed' => false,
                    'le_heading' => 180.0,
                    'he_ident' => '36',
                    'he_heading' => 360.0,
                ]
            );
        }

        $metars = [
            'EDDF' => ['last_update' => now(), 'metar' => 'AUTO 03010KT 360V060 CAVOK 12/02 Q1024 NOSIG',                                                                          'wind_direction' => 30,  'wind_speed' => 10, 'wind_gusts' => 0, 'temperature' => 12],
            'EDDS' => ['last_update' => now(), 'metar' => 'AUTO VRB03KT CAVOK 14/M01 Q1022 NOSIG',                                                                                 'wind_direction' => 0,   'wind_speed' => 0,  'wind_gusts' => 0, 'temperature' => 14],
            'EGLL' => ['last_update' => now(), 'metar' => 'COR AUTO 10006KT 010V140 9999 NCD 14/07 Q1026 NOSIG',                                                                   'wind_direction' => 100, 'wind_speed' => 6,  'wind_gusts' => 0, 'temperature' => 14],
            'EHAM' => ['last_update' => now(), 'metar' => '08006KT 010V130 9999 FEW027 11/03 Q1026 NOSIG',                                                                        'wind_direction' => 80,  'wind_speed' => 6,  'wind_gusts' => 0, 'temperature' => 11],
            'ENBR' => ['last_update' => now(), 'metar' => '36007KT 310V030 CAVOK 05/M07 Q1025 NOSIG RMK WIND 1200FT 02008KT',                                                     'wind_direction' => 360, 'wind_speed' => 7,  'wind_gusts' => 0, 'temperature' => 5],
            'ENGM' => ['last_update' => now(), 'metar' => '01012KT 340V040 CAVOK 07/M11 Q1021 NOSIG',                                                                             'wind_direction' => 10,  'wind_speed' => 12, 'wind_gusts' => 0, 'temperature' => 7],
            'ENTC' => ['last_update' => '2020-01-01 00:00:00', 'metar' => '31003KT 270V330 1800 -SN VV011 01/M01 Q1010 TEMPO 0800 SHSN VV006 RMK WIND 2600FT 34024KT',                            'wind_direction' => 310, 'wind_speed' => 3,  'wind_gusts' => 0, 'temperature' => 1],
            'ENTO' => ['last_update' => now(), 'metar' => '34006KT CAVOK 07/M10 Q1023 NOSIG',                                                                                     'wind_direction' => 340, 'wind_speed' => 6,  'wind_gusts' => 0, 'temperature' => 7],
            'KLAX' => ['last_update' => now(), 'metar' => '23005KT 10SM FEW012 BKN025 OVC049 13/12 A2988 RMK AO2 RAE38 SLP117 P0018 60029 T01330117 58007',                       'wind_direction' => 230, 'wind_speed' => 5,  'wind_gusts' => 0, 'temperature' => 13],
            'KORD' => ['last_update' => now(), 'metar' => '35003KT 10SM CLR 06/04 A3003 RMK AO2 SLP171 T00610039 53002',                                                          'wind_direction' => 350, 'wind_speed' => 3,  'wind_gusts' => 0, 'temperature' => 6],
            'KSFO' => ['last_update' => now(), 'metar' => '19005KT 10SM FEW012 BKN023 OVC045 13/09 A2984 RMK AO2 SLP103 T01280094 56005 $',                                       'wind_direction' => 190, 'wind_speed' => 5,  'wind_gusts' => 0, 'temperature' => 13],
            'LFPG' => ['last_update' => now(), 'metar' => '07010KT 040V100 CAVOK 16/02 Q1023 NOSIG',                                                                              'wind_direction' => 70,  'wind_speed' => 10, 'wind_gusts' => 0, 'temperature' => 16],
            'RJTT' => ['last_update' => now(), 'metar' => '17009KT 9999 FEW030 BKN/// 19/11 Q1017 NOSIG',                                                                         'wind_direction' => 170, 'wind_speed' => 9,  'wind_gusts' => 0, 'temperature' => 19],
            'ENSG' => ['last_update' => now(), 'metar' => 'AUTO 21004KT 110V260 9999 BKN045/// OVC177/// 01/M07 Q1022 RMK WIND 3806FT 26007KT',                                  'wind_direction' => 210, 'wind_speed' => 4,  'wind_gusts' => 0, 'temperature' => 1],
            'ENSD' => ['last_update' => now(), 'metar' => 'AUTO 27003KT 9999 FEW025/// OVC057/// 04/M03 Q1025 RMK WIND RWY 26 26005KT WIND 1126FT 28004KT',                      'wind_direction' => 270, 'wind_speed' => 3,  'wind_gusts' => 0, 'temperature' => 4],
            'EDDB' => ['last_update' => now(), 'metar' => 'AUTO 02007KT 340V070 CAVOK 11/00 Q1024 NOSIG',                                                                         'wind_direction' => 20,  'wind_speed' => 7,  'wind_gusts' => 0, 'temperature' => 11],
        ];

        foreach ($metars as $icao => $data) {
            $airport = Airport::where('icao', $icao)->first();
            if ($airport) {
                Metar::updateOrCreate(['airport_id' => $airport->id], $data);
            }
        }

        // Seed airport scores
        $entc = Airport::where('icao', 'ENTC')->first();
        if ($entc) {
            AirportScore::firstOrCreate(
                ['airport_id' => $entc->id, 'reason' => 'METAR_FOGGY'],
                ['score' => 1]
            );
        }
    }
}
