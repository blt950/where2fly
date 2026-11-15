<?php

namespace Tests\Feature;

use App\Models\Aircraft;
use App\Models\Airline;
use App\Models\Airport;
use App\Models\Flight;
use App\Models\FlightAircraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlightsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->airline('SAS', 'SK', 'Scandinavian');
        $this->airline('DLH', 'LH', 'Lufthansa');
        $this->flight('SAS', 'SK', '1', ['A320']);
        $this->flight('DLH', 'LH', '2', ['B738']);
        $this->flight('SAS', 'SK', '3', ['A320'], seenCounter: 2);
    }

    public function test_without_an_airline_every_airlines_flights_come_back_with_their_airline(): void
    {
        $response = $this->postJson(route('api.airport.flights'), $this->route());

        $response->assertOk();
        $this->assertEqualsCanonicalizing(['SAS1', 'DLH2'], collect($response->json('data.flights'))->pluck('flight_icao')->all());
        $this->assertSame(['Lufthansa', 'Scandinavian'], collect($response->json('data.airlines'))->pluck('name')->all());
        $this->assertSame('LH', collect($response->json('data.flights'))->firstWhere('flight_icao', 'DLH2')['airline']['iata_code']);
        $this->assertNull($response->json('data.airline'));
    }

    public function test_an_airline_narrows_the_flights_as_before(): void
    {
        $response = $this->postJson(route('api.airport.flights'), $this->route(['airlineId' => 'SAS']));

        $response->assertOk();
        $this->assertSame(['SAS1'], collect($response->json('data.flights'))->pluck('flight_icao')->all());
        $this->assertSame('Scandinavian', $response->json('data.airline.name'));
    }

    public function test_flights_with_a_searched_aircraft_are_highlighted(): void
    {
        $response = $this->postJson(route('api.airport.flights'), $this->route(['highlightedAircrafts' => ['B738']]));

        $highlighted = collect($response->json('data.flights'))->pluck('highlighted', 'flight_icao');
        $this->assertTrue($highlighted['DLH2']);
        $this->assertFalse($highlighted['SAS1']);
    }

    private function route(array $extra = []): array
    {
        return [
            'departureAirportId' => Airport::where('icao', 'ENGM')->value('id'),
            'arrivalAirportId' => $this->airports['EDDF']->id,
            ...$extra,
        ];
    }

    private function airline(string $icao, string $iata, string $name): void
    {
        (new Airline)->forceFill(['icao_code' => $icao, 'iata_code' => $iata, 'name' => $name])->save();
    }

    private function flight(string $airline, string $iata, string $number, array $aircrafts, int $seenCounter = 10): void
    {
        $flight = new Flight;
        $flight->forceFill([
            'airline_icao' => $airline,
            'airline_iata' => $iata,
            'flight_number' => $number,
            'flight_icao' => $airline . $number,
            'airport_dep_id' => Airport::where('icao', 'ENGM')->value('id'),
            'dep_icao' => 'ENGM',
            'airport_arr_id' => $this->airports['EDDF']->id,
            'arr_icao' => 'EDDF',
            'last_aircraft_icao' => $aircrafts[0],
            'seen_counter' => $seenCounter,
        ])->save();

        foreach ($aircrafts as $icao) {
            (new FlightAircraft)->forceFill(['flight_id' => $flight->id, 'aircraft_id' => Aircraft::firstOrCreate(['icao' => $icao])->id])->save();
        }
    }
}
