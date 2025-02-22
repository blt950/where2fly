@extends('layouts.app')

@section('meta-description')
    <meta name="description" content="Details of your search">
@endsection

@section('title', 'Results')
@section('content')

@section('resources')
    @vite('resources/js/sortable.js')
@endsection

@php
    $modalAirports = collect();
@endphp

    @include('layouts.title', ['title' => 'Search results'])

    <div class="container">   

        @if($searchVersionWarning)
            <div class="alert alert-warning mb-4">
                <p class="mb-0">
                    <i class="fas fa-warning"></i> {{ $searchVersionWarning }}</a>
                </p>
            </div>
        @endif

        @if($bearingWarning)
            <div class="alert alert-warning">
                <p class="mb-0">
                    <i class="fas fa-warning"></i> {{ $bearingWarning }}</a>
                </p>
            </div>
        @endif

        @if($suggestedAirport)
            <div class="d-flex flex-wrap justify-content-between">
                <h2>{{ ucfirst($direction) }} suggestion</h2>

                {{-- Add possibility to re-post the search query for a new random departure --}}
                <form id="form" method="GET" action="{{ route('search') }}">
                    @foreach($_GET as $key => $value)
                        @if($key != '_token')
                            @if(is_array($value))
                                @foreach($value as $subkey => $subvalue)
                                    <input type="hidden" name="{{ $key }}[{{ $subkey }}]" value="{{ $subvalue }}">
                                @endforeach
                            @else
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endif
                    @endforeach

                    <button id="randomiseBtn" type="submit" class="btn btn-sm btn-warning mb-1" style="font-size: 1rem;">Randomise <i class="fas fa-shuffle"></i></button>
                </form>

            </div>
        @else
            <h2>{{ ucfirst($direction) }}</h2>
        @endif
        <div class="results-container">
            <dl>
                <dt>Airport<dt>
                <dd>
                    <img class="flag" src="/img/flags/{{ strtolower($primaryAirport->iso_country) }}.svg" height="16" data-bs-toggle="tooltip" data-bs-title="{{ getCountryName($primaryAirport->iso_country) }}" alt="Flag of {{ getCountryName($primaryAirport->iso_country) }}"></img>
                    {{ $primaryAirport->icao }}
                </dd>
                <dd>{{ $primaryAirport->name }}</dd>
            </dl>

            <dl>
                <dt>Runway<dt>
                <dd class="rwy-feet">{{ number_format((int)$primaryAirport->longestRunway(), 0, '.', ',') }} ft</dd>
                <dd class="rwy-meters text-opacity-50">{{ number_format(round((int)$primaryAirport->longestRunway()* .3048), 0, '.', ',') }} m</dd>
            </dl>

            @if($primaryAirport->scores->count() > 0)
                <dl>
                    <dt>Conditions<dt>
                    <dd>
                    @foreach($primaryAirport->scores as $score)
                        @if(isset($filteredScores) && in_array($score->reason, $filteredScores))
                            <i 
                                class="text-success fas {{ App\Http\Controllers\ScoreController::$score_types[$score->reason]['icon'] }}"
                                data-bs-html="true"
                                data-bs-toggle="tooltip"
                                data-bs-title="{{ App\Http\Controllers\ScoreController::$score_types[$score->reason]['desc'] }}<br>{{ $score->data }}"
                            ></i>
                        @else
                            <i 
                                class="fas {{ App\Http\Controllers\ScoreController::$score_types[$score->reason]['icon'] }}"
                                data-bs-html="true"
                                data-bs-toggle="tooltip"
                                data-bs-title="{{ App\Http\Controllers\ScoreController::$score_types[$score->reason]['desc'] }}<br>{{ $score->data }}"
                            ></i>
                        @endif
                    @endforeach
                    </dd>
                </dl>
            @endif

            <dl>
                <dt>Weather<dt>
                <dd>
                    @if($primaryAirport->metar)
                        {{ \Carbon\Carbon::parse($primaryAirport->metar->last_update)->format('dHi\Z') }} {{ $primaryAirport->metar->metar }}
                        <span class="d-block mt-2"><button class="d-block btn btn-outline-secondary btn-sm" data-airport-icao="{{ $primaryAirport->icao }}" data-taf-button="true">Fetch TAF</button></span>
                    @else
                        <i class="fas fa-info-square"></i> No METAR available
                    @endif
                </dd>
            </dl>
        </div>

        <h2>{{ ($direction == 'departure') ? 'Arrival' : 'Departure' }} suggestions</h2>
        <div class="table-responsive">
            <table class="table table-hover text-start sortable asc mb-0">
                <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Airport</th>
                        <th scope="col">Distance</th>
                        <th scope="col">Time</th>
                        <th scope="col">Conditions</th>
                    </tr>
                    @if( !empty($sortByScores) && isset($suggestedAirports->first()->scores) && $suggestedAirports->first()->scores->count() == 0 )

                        <tr class="font-work-sans">
                            <th class="text-center text-info fw-normal pt-3 pb-3" colspan="9">
                                <i class="fas fa-info-square"></i> None of the airports in your range has interesting weather or ATC
                            </th>
                        </tr>

                    @endif
                </thead>
                <tbody>
                    @php $count = 1; @endphp

                    @foreach($suggestedAirports as $airport)
                        <tr class="pointer {{ ($count > 10) ? 'showmore-hidden' : null }}" data-airport-icao="{{ $airport->icao }}">
                            <th scope="row">{{ $count }}</th>
                            <td data-sort="{{ $airport->icao }}">
                                <div>
                                    <img class="flag" src="/img/flags/{{ strtolower($airport->iso_country) }}.svg" height="16" data-bs-toggle="tooltip" data-bs-title="{{ getCountryName($airport->iso_country) }}" alt="Flag of {{ getCountryName($airport->iso_country) }}"></img>
                                    {{ $airport->icao }}
                                </div>
                                {{ $airport->name }}
                            </td>
                            <td data-sort="{{ $airport->distance }}">{{ $airport->distance }}nm</td>
                            <td data-sort="{{ $airport->airtime }}">{{ gmdate('G:i', floor($airport->airtime * 3600)) }}h</td>
                            <td class="fs-5" data-sort={{ $airport->scores->count() }}>
                                @foreach($airport->scores as $score)
                                    @if(isset($filterByScores) && isset($filterByScores[$score->reason]) && $filterByScores[$score->reason] === 1)
                                        <i 
                                            class="text-success fas {{ App\Http\Controllers\ScoreController::$score_types[$score->reason]['icon'] }}"
                                            data-bs-html="true"
                                            data-bs-toggle="tooltip"
                                            data-bs-title="{{ App\Http\Controllers\ScoreController::$score_types[$score->reason]['desc'] }}<br>{{ $score->data }}"
                                        ></i>
                                    @else
                                        <i 
                                            class="fas {{ App\Http\Controllers\ScoreController::$score_types[$score->reason]['icon'] }}"
                                            data-bs-html="true"
                                            data-bs-toggle="tooltip"
                                            data-bs-title="{{ App\Http\Controllers\ScoreController::$score_types[$score->reason]['desc'] }}<br>{{ $score->data }}"
                                        ></i>
                                    @endif
                                @endforeach
                            </td>
                        </tr>

                        @php 
                            $modalAirports->push($airport);
                            $count++;
                        @endphp
                    @endforeach

                    @if($count == 1)
                        <tr>
                            <th colspan="9" class="text-center text-danger">
                                <i class="fas fa-exclamation-triangle"></i> No results matched your criteria
                            </th>
                        </tr>
                    @elseif($count > 10)
                        <tr id="showMoreRow">
                            <th colspan="10" class="text-center text-danger">
                                <button id="showMoreBtn" class="btn btn-secondary">Show more</button>
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
    @vite('resources/js/functions/searchResults.js')
    @vite('resources/js/functions/taf.js')
    <script>
        var airportMapData = {!! isset($airportCoordinates) ? json_encode($airportCoordinates) : '[]' !!}
        var primaryAirport = '{{ $primaryAirport->icao }}';
        var direction = '{{ $direction }}';
        var highlightedAircrafts = {!! isset($filterByAircrafts) ? json_encode($filterByAircrafts) : '[]' !!};

        // Listen for the custom event indicating the map is ready
        window.addEventListener('mapReady', function() {
            setCluster(false);
            setPrimaryAirport(primaryAirport);
            setReverseDirection((direction == 'departure' ? false : true));
            setAirportsData(airportMapData);
            setHighlightedAircrafts(highlightedAircrafts);
        });

        // Add click event listener to each table row
        document.addEventListener("DOMContentLoaded", function() {
            const rows = document.querySelectorAll('tr[data-airport-icao]');
            rows.forEach(row => {
                row.addEventListener('click', function() {
                    // Get the lat and lon from data attributes
                    const icao = this.getAttribute('data-airport-icao');

                    setFocusAirport(icao);

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