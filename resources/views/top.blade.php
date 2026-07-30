@extends('layouts.app')

@section('meta-description')
    <meta name="description" content="Destinations with the worst weather right now">
@endsection

@section('title', 'Top List')
@section('content')

    @section('resources')
        @vite('resources/js/sortable.js')
    @endsection

    @include('layouts.title', ['title' => 'Top Airports Right Now', 'subtitle' => 'Destinations with the worst weather right now'])
    
    <div class="container">

        @php
            // Each dropdown links to a URL keeping the other two filters intact.
            // JM is the default aircraft, so "no filter" must travel as an
            // explicit aircraft=all rather than an absent param
            $topRoute = fn ($params) => route(! empty($params['continent']) ? 'top.filtered' : 'top', array_filter($params));
            $aircraftParam = $aircraft ?? 'all';
        @endphp

        <div class="d-flex flex-wrap gap-2 mb-3">
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    Continent: {{ $continent ? \App\Http\Controllers\TopController::CONTINENTS[$continent] : 'All' }}
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item {{ $continent === null ? 'active' : '' }}" href="{{ $topRoute(['exclude' => $exclude, 'aircraft' => $aircraftParam]) }}">All</a></li>
                    @foreach(\App\Http\Controllers\TopController::CONTINENTS as $code => $name)
                        <li><a class="dropdown-item {{ $continent == $code ? 'active' : '' }}" href="{{ $topRoute(['continent' => $code, 'exclude' => $exclude, 'aircraft' => $aircraftParam]) }}">{{ $name }}</a></li>
                    @endforeach
                </ul>
            </div>

            <div class="dropdown">
                <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    Aircraft Type: {{ $aircraft ? \App\Helpers\AircraftHelper::name($aircraft) : 'All' }}
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item {{ $aircraft === null ? 'active' : '' }}" href="{{ $topRoute(['continent' => $continent, 'exclude' => $exclude, 'aircraft' => 'all']) }}">All</a></li>
                    @foreach(\App\Helpers\AircraftHelper::TYPES as $code => $type)
                        <li><a class="dropdown-item {{ $aircraft == $code ? 'active' : '' }}" href="{{ $topRoute(['continent' => $continent, 'exclude' => $exclude, 'aircraft' => $code]) }}">{{ $type['name'] }}</a></li>
                    @endforeach
                </ul>
            </div>

            <div class="dropdown">
                <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    VATSIM: {{ $exclude == 'vatsim' ? 'Excluded' : 'Included' }}
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item {{ $exclude === null ? 'active' : '' }}" href="{{ $topRoute(['continent' => $continent, 'aircraft' => $aircraftParam]) }}">Included</a></li>
                    <li><a class="dropdown-item {{ $exclude == 'vatsim' ? 'active' : '' }}" href="{{ $topRoute(['continent' => $continent, 'exclude' => 'vatsim', 'aircraft' => $aircraftParam]) }}">Excluded</a></li>
                </ul>
            </div> 
        </div>
            
        <div class="table-responsive">
            <table class="table table-hover text-start sortable asc mb-0">
                <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Airport</th>
                        <th scope="col" width="10%">Forecast</th>
                    </tr>
                </thead>
                <tbody>
                    @php $count = 1; @endphp
                    @foreach($airports as $airport)
                        <tr class="pointer" data-airport-icao={{ $airport->icao }}>
                            <th scope="row">{{ $count }}</th>
                            <td data-sort="{{ $airport->icao }}">
                                <div>
                                    <img class="flag" src="/img/flags/{{ strtolower($airport->iso_country) }}.svg" height="16" data-bs-toggle="tooltip" data-bs-title="{{ getCountryName($airport->iso_country) }}" alt="Flag of {{ getCountryName($airport->iso_country) }}"></img>
                                    {{ $airport->icao }}
                                </div>
                                {{ $airport->name }}
                            </td>
                            <td class="fs-5" data-sort="{{ $airport->displayScores()->count() }}">
                                @foreach($airport->displayScores() as $score)
                                    <x-score-icon :score="$score" :airport="$airport" />
                                @endforeach
                            </td>
                        </tr>
                        @php $count++; @endphp
                    @endforeach
                    @if($count == 1)
                        <tr>
                            <th colspan="9" class="text-center text-warning">
                                <i class="fa-sharp fa-exclamation-triangle"></i> No top airports available. Weather database could be updating, please try again in a few minutes.
                            </th>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
        @include('layouts.legend')
    </div>
@endsection

@section('js')

    @vite('resources/js/functions/tooltip.js')
    <script>
        var airportMapData = {!! isset($airportMapData) ? $airportMapData : '[]' !!}
        var focusContinent = {!! isset($continent) ? '\''.$continent.'\'' : 'null' !!};

        // Listen for the custom event indicating the map is ready
        window.addEventListener('mapReady', function() {
            setAirportsData(airportMapData);
        });

        // Add click event listener to each table row
        document.addEventListener("DOMContentLoaded", function() {
            const rows = document.querySelectorAll('tr[data-airport-icao]');
            rows.forEach(row => {
                row.addEventListener('click', function() {
                    // Get the lat and lon from data attributes
                    const icao = this.getAttribute('data-airport-icao');

                    // Set the coordinates in the React component
                    if (window.setFocusAirport) {
                        window.setFocusAirport(icao);
                    }

                    // Radar blip on the map at the selected airport
                    if (window.pingAirport) {
                        window.pingAirport(icao);
                    }

                    // Remove 'active' class from all rows and add to the clicked row
                    rows.forEach(r => r.classList.remove('active'));
                    this.classList.add('active');
                });
            });
        });

        // Event listener if user clicks on map dot, to mark active in table
        window.addEventListener('mapFocusAirport', function(event) {
            const focusAirport = event.detail.focusAirport;
            
            const rows = document.querySelectorAll('tr[data-airport-icao]');
            rows.forEach(r => r.classList.remove('active'));

            const focusRow = document.querySelector(`tr[data-airport-icao="${focusAirport}"]`);
            focusRow.classList.add('active');

            // Scroll the table to the selected airport, unless it's already in view
            const rect = focusRow.getBoundingClientRect();
            if (rect.top < 0 || rect.bottom > window.innerHeight) {
                focusRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
        
    </script>
@endsection