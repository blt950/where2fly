@extends('layouts.app')

@section('meta-description')
<meta name="description" content="Details of your search">
@endsection

@section('title', 'Results')
@section('content')

@section('resources')
@vite('resources/js/sortable.js')
@endsection

    @include('layouts.title', ['title' => 'Search results'])

    <div class="container">
        <div class="results-container d-flex align-items-center">
            <dl>
                <dt>Departure<dt>
                <dd>
                    <img class="flag" src="/img/flags/{{ strtolower($departure->iso_country) }}.svg" height="16" data-bs-toggle="tooltip" data-bs-title="{{ getCountryName($departure->iso_country) }}" alt="Flag of {{ getCountryName($departure->iso_country) }}"></img>
                    {{ $departure->icao }}
                </dd>
                <dd>{{ $departure->name }}</dd>
            </dl>

            <i class="fas fa-arrow-right fs-2"></i>

            <dl>
                <dt>Arrival<dt>
                <dd>
                    <img class="flag" src="/img/flags/{{ strtolower($arrival->iso_country) }}.svg" height="16" data-bs-toggle="tooltip" data-bs-title="{{ getCountryName($arrival->iso_country) }}" alt="Flag of {{ getCountryName($arrival->iso_country) }}"></img>
                    {{ $arrival->icao }}
                </dd>
                <dd>{{ $arrival->name }}</dd>
            </dl>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <h2>Routes</h2>
            <p class="mb-0">Data since {{ $routes->sort()->first()->first_seen_at->format('Y-m-d') }}</p>
        </div>
        
        <div class="scroll-fade">
            <div class="table-responsive">
                <table class="table table-hover text-start sortable asc">
                    <thead>
                        <tr>
                            <th scope="col" width="25%">Flight</th>
                            <th scope="col">Airline</th>
                            <th scope="col" width="10%">Aircraft</th>
                            <th scope="col" width="25%">Last seen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($routes as $route)
                            <tr>
                                <td data-sort="{{ $route->flight_icao }}">
                                    <strong>{{ $route->flight_icao }}</strong>
                                    <a class="d-block fs-6 text-info link-underline-info link-underline-opacity-25-hover font-work-sans ps-0" href="https://dispatch.simbrief.com/options/custom?orig={{ $departure->icao }}&dest={{ $arrival->icao }}&airline={{ $route->airline->icao_code }}&fltnum={{ $route->flight_number }}" target="_blank">
                                        <span>SimBrief</span> <i class="fas fa-up-right-from-square"></i>
                                    </a>
                                    <a class="d-block fs-6 text-info link-underline-info link-underline-opacity-25-hover font-work-sans ps-0" href="https://www.flightradar24.com/data/flights/{{ strtolower($route->airline->iata_code . $route->flight_number) }}" rel="noreferrer" target="_blank">
                                        <span>FR24</span> <i class="fas fa-up-right-from-square"></i>
                                    </a>
                                </td>
                                <td data-sort="{{ $route->airline->iata_code }}">
                                    <img
                                        class="airline-logo small nopadding" 
                                        src="{{ asset('img/airlines/'.$route->airline->iata_code.'.png') }}"
                                        alt=""
                                    >
                                    {{ $route->airline->name }}
                                </td>
                                <td>
                                    {{ $route->aircrafts->pluck('icao')->sort()->implode(', ') }}
                                </td>
                                <td>{{ $route->last_seen_at->format('Y-m-d') }}</td>
                            </tr>
                        @endforeach                        
                    </tbody>
                </table>
            </div>
        </div>
        @include('layouts.legend')
    </div>
@endsection

@section('js')
    <script>
        var airportMapData = {!! isset($airportCoordinates) ? json_encode($airportCoordinates) : '[]' !!}
        var departure = '{{ $departure->icao }}';
        var arrival = '{{ $arrival->icao }}';

        // Listen for the custom event indicating the map is ready
        window.addEventListener('mapReady', function() {
            setAirportsData(airportMapData);
            setDrawRoute([departure, arrival]);
        });
    </script>
@endsection
