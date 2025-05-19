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

        <div class="filterbox">
            <span class="m-0"><strong class="d-block">Filter</strong>
                <a class="btn btn-sm {{ Route::is('top') ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('top') }}">All</a>
                <a class="btn btn-sm {{ $continent == 'AF' ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('top.filtered', 'AF') }}">Africa</a>
                <a class="btn btn-sm {{ $continent == 'AS' ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('top.filtered', 'AS') }}">Asia</a>
                <a class="btn btn-sm {{ $continent == 'EU' ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('top.filtered', 'EU') }}">Europe</a>
                <a class="btn btn-sm {{ $continent == 'NA' ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('top.filtered', 'NA') }}">North America</a>
                <a class="btn btn-sm {{ $continent == 'OC' ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('top.filtered', 'OC') }}">Oceania</a>
                <a class="btn btn-sm {{ $continent == 'SA' ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('top.filtered', 'SA') }}">South America</a>
            </span>

            <span><strong class="d-block">VATSIM Conditions</strong>
                <a class="btn btn-sm {{ $exclude === null ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route(Route::currentRouteName(), $continent) }}">Include</a>
                <a class="btn btn-sm {{ $exclude == 'vatsim' ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route(Route::currentRouteName(), $continent) }}?exclude=vatsim">Exclude</a>
            </span>
        </div>
            
        <div class="table-responsive">
            <table class="table table-hover text-start sortable asc">
                <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Airport</th>
                        <th scope="col" width="10%">Conditions</th>
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
                            <td class="fs-5" data-sort="{{ $airport->scores->count() }}">
                                @foreach($airport->scores as $score)
                                    <i 
                                        class="fas {{ App\Http\Controllers\ScoreController::$score_types[$score->reason]['icon'] }}"
                                        data-bs-html="true"
                                        data-bs-toggle="tooltip"
                                        data-bs-title="{{ App\Http\Controllers\ScoreController::$score_types[$score->reason]['desc'] }}<br>{{ $score->data }}"
                                    ></i>
                                @endforeach
                            </td>
                        </tr>
                        @php $count++; @endphp
                    @endforeach
                    @if($count == 1)
                        <tr>
                            <th colspan="9" class="text-center text-danger">
                                <i class="fas fa-exclamation-triangle"></i> No top airports available. Weather database could be updating, please try again in a few minutes.
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
        });
        
    </script>
@endsection