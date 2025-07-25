<?php

namespace App\Console\Commands;

use App\Models\Aircraft;
use App\Models\Flight;
use App\Models\FlightAircraft;
use Illuminate\Console\Command;

class EnrichFlights extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'enrich:flights';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enrich flight data with aircraft types';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Get flights that have been seen within the last 6 hours and upsert the aircraft_icao to the flight_aircrafts table
        $flights = Flight::where('last_seen_at', '>=', now()->subHours(6))->get();
        $upsertAircraftData = [];
        $aircraftTypeConversions = [
            '0000' => null,
            '100' => 'F100',
            '141' => 'B461',
            '142' => 'B462',
            '143' => 'B463',
            '14X' => 'B461',
            '14Y' => 'B462',
            '14Z' => 'B463',
            '221' => 'BCS1',
            '223' => 'BCS3',
            '290' => 'E290',
            '295' => 'E295',
            '310' => 'A310',
            '312' => 'A310',
            '313' => 'A310',
            '318' => 'A318',
            '319' => 'A319',
            '31F' => 'A310',
            '31N' => 'A19N',
            '31X' => 'A310',
            '31Y' => 'A310',
            '320' => 'A320',
            '321' => 'A321',
            '32A' => 'A320',
            '32C' => 'A318',
            '32D' => 'A319',
            '32N' => 'A20N',
            '32Q' => 'A21N',
            '330' => 'A330',
            '332' => 'A332',
            '333' => 'A333',
            '338' => 'A338',
            '339' => 'A339',
            '33X' => 'A332',
            '340' => 'A340',
            '342' => 'A342',
            '343' => 'A343',
            '345' => 'A345',
            '346' => 'A346',
            '351' => 'A35K',
            '359' => 'A359',
            '380' => 'A388',
            '703' => 'B703',
            '70F' => 'B703',
            '70M' => 'B703',
            '717' => 'B712',
            '721' => 'B721',
            '722' => 'B722',
            '72B' => 'B721',
            '72C' => 'B722',
            '72S' => 'B722',
            '72W' => 'B721',
            '72X' => 'B721',
            '72Y' => 'B722',
            '731' => 'B731',
            '732' => 'B732',
            '733' => 'B733',
            '734' => 'B734',
            '735' => 'B735',
            '736' => 'B736',
            '737' => 'B738',
            '738' => 'B738',
            '739' => 'B739',
            '73C' => 'B733',
            '73E' => 'B735',
            '73F' => 'B732',
            '73G' => 'B737',
            '73H' => 'B738',
            '73J' => 'B739',
            '73K' => 'B738',
            '73L' => 'B732',
            '73P' => 'B734',
            '73Q' => 'B734',
            '73R' => 'B737',
            '73W' => 'B737',
            '73X' => 'B732',
            '73Y' => 'B733',
            '741' => 'B741',
            '742' => 'B742',
            '743' => 'B743',
            '744' => 'B744',
            '748' => 'B748',
            '74B' => 'B744',
            '74C' => 'B742',
            '74D' => 'B743',
            '74E' => 'B744',
            '74J' => 'B744',
            '74L' => 'N74S',
            '74R' => 'B74R',
            '74T' => 'B741',
            '74U' => 'B743',
            '74V' => 'B74R',
            '74X' => 'B742',
            '74Y' => 'B744',
            '752' => 'B752',
            '753' => 'B753',
            '75F' => 'B752',
            '75M' => 'B752',
            '75T' => 'B753',
            '75W' => 'B752',
            '762' => 'B762',
            '763' => 'B763',
            '764' => 'B764',
            '76W' => 'B763',
            '76V' => 'B763',
            '76X' => 'B762',
            '76Y' => 'B763',
            '772' => 'B772',
            '773' => 'B773',
            '77L' => 'B77L',
            '77W' => 'B77W',
            '77X' => 'B77L',
            '781' => 'B78X',
            '788' => 'B788',
            '789' => 'B789',
            '7M7' => 'B37M',
            '7M8' => 'B38M',
            '7M9' => 'B39M',
            '7MJ' => 'B3XM',
            'A22' => 'AN22',
            'A26' => 'AN26',
            'A28' => 'AN28',
            'A30' => 'AN30',
            'A32' => 'AN32',
            'A38' => 'AN38',
            'A40' => 'A140',
            'A4F' => 'A124',
            'A5F' => 'A225',
            'A81' => 'A148',
            'AB3' => 'A30B',
            'AB4' => 'A30B',
            'AB6' => 'A306',
            'ABB' => 'A3ST',
            'ABF' => 'A30B',
            'ABX' => 'A30B',
            'ABY' => 'A306',
            'ACP' => 'AC68',
            'ACT' => 'AC90',
            'AGH' => 'A109',
            'ALM' => 'LOAD',
            'AN4' => 'AN24',
            'AN7' => 'AN72',
            'ANF' => 'AN12',
            'AR1' => 'RJ1H',
            'AR7' => 'RJ70',
            'AR8' => 'RJ85',
            'AT4' => 'AT43',
            'AT5' => 'AT45',
            'AT7' => 'AT72',
            'ATD' => 'AT44',
            'ATF' => 'AT72',
            'AX1' => 'RX1H',
            'AX8' => 'RX85',
            'B11' => 'BA11',
            'B12' => 'BA11',
            'B13' => 'BA11',
            'B14' => 'BA11',
            'B15' => 'BA11',
            'B72' => 'B720',
            'BE1' => 'B190',
            'BEH' => 'B190',
            'BES' => 'B190',
            'BNI' => 'BN2P',
            'BNT' => 'TRIS',
            'C27' => 'AJ27',
            'CCJ' => 'CL60',
            'CCX' => 'GLEX',
            'CD2' => 'NOMA',
            'CL4' => 'CL44',
            'CN7' => 'C750',
            'CR1' => 'CRJ1',
            'CR2' => 'CRJ2',
            'CR7' => 'CRJ7',
            'CR9' => 'CRJ9',
            'CRA' => 'CRJ9',
            'CRK' => 'CRJX',
            'CRV' => 'S210',
            'CS1' => 'BCS1',
            'CS2' => 'C212',
            'CS3' => 'BCS3',
            'CS5' => 'CN35',
            'CV2' => 'CVLP',
            'CV4' => 'CVLP',
            'CV5' => 'CVLT',
            'CVV' => 'CVLP',
            'CVX' => 'CVLP',
            'CVY' => 'CVLT',
            'CWC' => 'C46',
            'D10' => 'DC10',
            'D11' => 'DC10',
            'D1C' => 'DC10',
            'D1F' => 'DC10',
            'D1M' => 'DC10',
            'D1X' => 'DC10',
            'D1Y' => 'DC10',
            'D28' => 'D228',
            'D38' => 'D328',
            'D3F' => 'DC3',
            'D6F' => 'DC6',
            'D8L' => 'DC86',
            'D8Q' => 'DC87',
            'D8T' => 'DC85',
            'D8Y' => 'DC87',
            'D91' => 'DC91',
            'D92' => 'DC92',
            'D93' => 'DC93',
            'D94' => 'DC94',
            'D95' => 'DC95',
            'D9C' => 'DC93',
            'D9D' => 'DC94',
            'D9X' => 'DC91',
            'DH1' => 'DH8A',
            'DH2' => 'DH8B',
            'DH3' => 'DH8C',
            'DH4' => 'DH8D',
            'DH7' => 'DHC7',
            'DHC' => 'DHC4',
            'DHD' => 'DOVE',
            'DHH' => 'HERN',
            'DHL' => 'DHC3',
            'DHO' => 'DHC3',
            'DHP' => 'DHC2',
            'DHR' => 'DH2T',
            'DHS' => 'DHC3',
            'DHT' => 'DHC6',
            'E70' => 'E170',
            'E75' => 'E75L',
            'E90' => 'E190',
            'E95' => 'E195',
            'EM2' => 'E120',
            'EMB' => 'E110',
            'ER3' => 'E135',
            'ER4' => 'E145',
            'ERK' => 'E145',
            'F21' => 'F28',
            'F22' => 'F28',
            'F23' => 'F28',
            'F24' => 'F28',
            'F5F' => 'F50',
            'FK7' => 'F27',
            'FRJ' => 'J328',
            'GRG' => 'G21',
            'GRM' => 'G73T',
            'GRS' => 'G159',
            'HS7' => 'A748',
            'I14' => 'I114',
            'I93' => 'IL96',
            'I9F' => 'IL96',
            'I9M' => 'IL96',
            'I9X' => 'IL96',
            'I9Y' => 'IL96',
            'IL6' => 'IL62',
            'IL7' => 'IL76',
            'IL8' => 'IL18',
            'IL9' => 'IL96',
            'ILW' => 'IL86',
            'J31' => 'JS31',
            'J32' => 'JS32',
            'J41' => 'JS41',
            'JU5' => 'JU52',
            'L10' => 'L101',
            'L11' => 'L101',
            'L15' => 'L101',
            'L1F' => 'L101',
            'L49' => 'CONI',
            'L4T' => 'L410',
            'LOE' => 'L188',
            'LOF' => 'L188',
            'LOH' => 'C130',
            'LOM' => 'L188',
            'M11' => 'MD11',
            'M1F' => 'MD11',
            'M1M' => 'MD11',
            'M80' => 'MD80',
            'M81' => 'MD81',
            'M82' => 'MD82',
            'M83' => 'MD83',
            'M87' => 'MD87',
            'M88' => 'MD88',
            'M90' => 'MD90',
            'ND2' => 'N262',
            'NDC' => 'S601',
            'NDH' => 'S65C',
            'PL2' => 'PC12',
            'PL6' => 'PC6T',
            'PN6' => 'P68',
            'S20' => 'SB20',
            'S58' => 'S58T',
            'SF3' => 'SF34',
            'SFB' => 'SF34',
            'SFF' => 'SF34',
            'SH3' => 'SH33',
            'SH6' => 'SH36',
            'SHB' => 'BELF',
            'SHS' => 'SC7',
            'SSC' => 'CONC',
            'SU9' => 'SU95',
            'T20' => 'T204',
            'T2F' => 'T204',
            'TU3' => 'T134',
            'TU5' => 'T154',
            'VCV' => 'VISC',
            'WWP' => 'WW24',
            'YK2' => 'YK42',
            'YK4' => 'YK40',
            'YN2' => 'Y12',
            'YN7' => 'AN24',
            'YS1' => 'YS11',
            'ZZZZ' => null,
        ];

        $aircraftCache = []; // Aircraft cache to avoid multiple database queries for the same aircraft type.

        foreach ($flights as $flight) {
            // Directly attempt to get the converted aircraft type or fallback to the original ICAO code.
            if (array_key_exists($flight->last_aircraft_icao, $aircraftTypeConversions) && $aircraftTypeConversions[$flight->last_aircraft_icao] == null) {
                continue;
            }

            $aircraftType = $aircraftTypeConversions[$flight->last_aircraft_icao] ?? $flight->last_aircraft_icao;

            // Use firstOrCreate method to either find the existing aircraft or create a new one, thereby reducing the code complexity and potential for duplicated entries.
            if (! isset($aircraftCache[$aircraftType])) {
                $aircraftCache[$aircraftType] = Aircraft::firstOrCreate(['icao' => $aircraftType], ['icao' => $aircraftType]);
            }

            $aircraft = $aircraftCache[$aircraftType];

            // Prepare the data for bulk insertion/upsert after the loop.
            $upsertAircraftData[] = [
                'flight_id' => $flight->id,
                'aircraft_id' => $aircraft->id,
                'last_seen_at' => $flight->last_seen_at,
            ];
        }

        // Split array into chunks of 4000 each and upsert each individually
        foreach (array_chunk($upsertAircraftData, 4000) as $chunk) {
            FlightAircraft::upsert(
                $chunk,
                ['flight_id', 'aircraft_icao'],
                ['last_seen_at'],
            );
        }

        return Command::SUCCESS;
    }
}
