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

            <span><strong class="d-block">VATSIM</strong>
                <a class="btn btn-sm {{ $exclude === null ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route(Route::currentRouteName(), $continent) }}">Include</a>
                <a class="btn btn-sm {{ $exclude == 'vatsim' ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route(Route::currentRouteName(), $continent) }}?exclude=vatsim">Exclude</a>
            </span>
        </div>
            
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
                    <tr class="pointer" data-airport="{{ $airport->icao }}">
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
                            <i class="fas fa-exclamation-triangle"></i> No top airports available in this area, the weather is too nice
                        </th>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="popup-container">
        {{-- Let's draw all airport cards here --}}
        @foreach($airports as $airport)
            @include('search.parts.mapCard', ['airport' => $airport])
        @endforeach
    </div>

    @include('layouts.legend')
@endsection

@section('js')
    <script>
        // When DOM is ready, draw the primary airport
        airportCoordinates = {!! isset($airportCoordinates) ? json_encode($airportCoordinates) : '[]' !!}

        document.addEventListener('DOMContentLoaded', function() {
            if(airportCoordinates !== undefined){

                var stepIcon = L.icon({
                    iconUrl: '{{ asset('img/circle.svg') }}',
                    iconSize: [12, 12],
                });

                var markers = L.markerClusterGroup({
                    showCoverageOnHover: false,
                });

                Object.keys(airportCoordinates).forEach(airport => {
                    var marker = new L.marker([airportCoordinates[airport]['lat'], airportCoordinates[airport]['lon']], { icon:stepIcon });
                    marker.bindTooltip(airport, {permanent: true, direction: 'left', className: "airport"});
                    markers.addLayer(marker);
                });

                map.addLayer(markers);
            }
        });
    </script>

    <script>

        // When table row is howered, fetch the data-airport attribute and show the corresponding popup
        document.querySelectorAll('tbody > tr').forEach(function(element) {
            element.addEventListener('click', function() {
                if(this.dataset && this.dataset.airport){
                    var airport = this.dataset.airport

                    // Add "active" to the clicked row
                    document.querySelectorAll('tbody > tr').forEach(function(element) {
                        element.classList.remove('active')
                    });
                    this.classList.add('active')


                    // Remove show class from all popups
                    document.querySelectorAll('.popup-container > div').forEach(function(element) {
                        element.classList.remove('show')
                    });

                    map.panTo([airportCoordinates[airport]['lat'], airportCoordinates[airport]['lon']], {animate: true, duration: 0.5, easeLinearity: 0.25});

                    document.querySelector('.popup-container').querySelector('[data-airport="' + airport + '"]').classList.add('show')
                }
            });
        });

    </script>
    @include('scripts.tooltip')
    @include('scripts.taf')
    @include('scripts.map')
@endsection