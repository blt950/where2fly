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

                {{-- Add possiblity to re-post the search query for a new random departure --}}
                <form id="form" method="POST" action="{{ route('search') }}">
                    @csrf

                    @foreach($_POST as $key => $value)
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
                <dt>Airport
                    @if($suggestedAirport)
                        <span class="badge rounded-pill text-bg-info fs-7">Based on filter</span>
                    @endif
                <dt>
                <dd>
                    <img class="flag" src="/img/flags/{{ strtolower($primaryAirport->iso_country) }}.svg" height="16" data-bs-toggle="tooltip" data-bs-title="{{ getCountryName($primaryAirport->iso_country) }}" alt="Flag of {{ getCountryName($primaryAirport->iso_country) }}"></img>
                    {{ $primaryAirport->icao }}
                </dd>
                <dd>{{ $primaryAirport->name }}</dd>
            </dl>

            <dl>
                <dt>Runway<dt>
                <dd class="rwy-feet">{{ $primaryAirport->longestRunway() }}</dd>
                <dd class="rwy-meters text-muted">{{ round($primaryAirport->longestRunway()* .3048) }}</dd>
            </dl>

            <dl>
                <dt>Conditions<dt>
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
            </dl>

            <div class="w-100">
                @if($primaryAirport->metar)
                    <div class="d-flex mb-3 nav nav-pills" style="font-size: 0.75rem">
                        <div>
                            <button class="nav-link active" id="home-tab-{{ $primaryAirport->id }}" data-bs-toggle="tab" data-bs-target="#metar-pane-{{ $primaryAirport->id }}" type="button" role="tab">METAR</button>
                        </div>
                        <div>
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#taf-pane-{{ $primaryAirport->id }}" data-taf-button="true" data-primaryAirport-icao="{{ $primaryAirport->icao }}" type="button" role="tab">TAF</button>
                        </div>
                    </div>
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="metar-pane-{{ $primaryAirport->id }}" role="tabpanel" aria-labelledby="home-tab-{{ $primaryAirport->id }}" tabindex="0">{{ \Carbon\Carbon::parse($primaryAirport->metar->last_update)->format('dHi\Z') }} {{ $primaryAirport->metar->metar }}</div>
                        @isset($tafs[$primaryAirport->icao])
                            <div class="tab-pane fade" id="taf-pane-{{ $primaryAirport->id }}" role="tabpanel" tabindex="0">{{ $tafs[$primaryAirport->icao] }}</div>
                        @else
                            <div class="tab-pane fade" id="taf-pane-{{ $primaryAirport->id }}" role="tabpanel" tabindex="0">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                            </div>
                        @endif
                    </div>
                @else
                    <dl>
                        <dt>METAR<dt>
                        <dd class="text-info"><i class="fas fa-info-square"></i> No METAR available</dd>
                    </dl>
                @endif
            </div>
        </div>

        <h2>{{ ($direction == 'departure') ? 'Arrival' : 'Departure' }} suggestions</h2>
        <table class="table table-hover text-start sortable asc">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Airport</th>
                    <th scope="col">Distance</th>
                    <th scope="col">Time</th>
                    <th scope="col">Conditions</th>
                    {{-- <th scope="col">Airlines</th>
                    <th scope="col">Runway</th>
                    <th scope="col" class="no-sort" width="40%">Details</th> --}}
                </tr>
            </thead>
            <tbody>
                @php $count = 1; @endphp

                @if( !empty($sortByScores) && isset($suggestedAirports->first()->scores) && $suggestedAirports->first()->scores->count() == 0 )

                    <tr class="font-work-sans">
                        <th class="text-center text-info fw-normal pt-3 pb-3" colspan="9">
                            <i class="fas fa-info-square"></i> None of the airports in your range has interesting weather or ATC
                        </th>
                    </tr>

                @endif

                @foreach($suggestedAirports as $airport)
                    <tr class="{{ ($count > 10) ? 'showmore-hidden' : null }}">
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
                        {{-- <td 
                            @if($airport->airlines->isNotEmpty())
                                data-sort="{{ $airport->airlines->count() }}"
                            @else
                                data-sort="0"
                            @endif
                        >
                            
                            @if($airport->airlines->isNotEmpty())
                                @foreach($airport->airlines as $airline)

                                    @php $highlight = $airport->flights->where('airline_icao', $airline->icao_code)->contains(fn($flight) => $flight->aircrafts->whereIn('icao', $filterByAircrafts)->isNotEmpty()); @endphp
                                    <button type="button" class="airline-button {{ $highlight ? 'highlight' : null }}" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#{{ $primaryAirport->icao . '-' . $airport->icao . '-' . $airline->icao_code }}-Modal">
                                        <img
                                            data-bs-toggle="tooltip"
                                            data-bs-title="See all {{ $airline->name }} flights"
                                            class="airline-logo" 
                                            src="{{ asset('img/airlines/'.$airline->iata_code.'.png') }}"
                                        >
                                    </button>
                                @endforeach
                            @else
                                -
                            @endif
                        </td>
                        <td data-sort="{{ $airport->longestRunway() }}">
                            <div class="rwy-feet">{{ $airport->longestRunway() }}</div>
                            <div class="rwy-meters text-black text-opacity-50">{{ round($airport->longestRunway()* .3048) }}</div>
                        </td>
                        <td>
                            <div class="d-flex justify-content-between mb-3 nav nav-pills" style="font-size: 0.75rem">
                                <div class="d-flex">
                                    <div>
                                        <button class="nav-link active" id="home-tab-{{ $airport->id }}" data-bs-toggle="tab" data-bs-target="#metar-pane-{{ $airport->id }}" type="button" role="tab">METAR</button>
                                    </div>
                                    <div>
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#taf-pane-{{ $airport->id }}" data-taf-button="true" data-airport-icao="{{ $airport->icao }}" type="button" role="tab">TAF</button>
                                    </div>
                                </div>

                                <div class="d-flex hover-show-group">
                                    <div class="hover-show secondary">
                                        <a class="btn btn-sm float-end font-work-sans text-muted" href="https://windy.com/{{ $airport->icao }}" target="_blank">
                                            <span class="d-none d-lg-inline d-xl-inline">Windy</span> <i class="fas fa-up-right-from-square"></i>
                                        </a>
                                    </div>
                                    <div class="hover-show">
                                        @php
                                            $simbriefUrl = 'orig=' . ($direction == 'departure' ? $primaryAirport->icao : $airport->icao ) . '&dest=' . ($direction == 'departure' ? $airport->icao : $primaryAirport->icao);
                                        @endphp
                                        <a class="btn btn-sm float-end font-work-sans text-muted" href="https://dispatch.simbrief.com/options/custom?{{ $simbriefUrl }}" target="_blank">
                                            <span class="d-none d-lg-inline d-xl-inline">SimBrief</span> <i class="fas fa-up-right-from-square"></i>
                                        </a>
                                    </div>
                                </div>
                            </div> --}}
                            
                            {{-- <div class="tab-content">
                                {{-- METAR tab 
                                <div class="tab-pane fade show active" id="metar-pane-{{ $airport->id }}" role="tabpanel" aria-labelledby="home-tab-{{ $airport->id }}" tabindex="0">{{ \Carbon\Carbon::parse($airport->metar->last_update)->format('dHi\Z') }} {{ $airport->metar->metar }}</div>
                                
                                {{-- TAF tab 
                                @isset($tafs[$airport->icao])
                                    <div class="tab-pane fade" id="taf-pane-{{ $airport->id }}" role="tabpanel" tabindex="0">{{ $tafs[$airport->icao] }}</div>
                                @else
                                    <div class="tab-pane fade" id="taf-pane-{{ $airport->id }}" role="tabpanel" tabindex="0">
                                        <span class="spinner-border spinner-border-sm" role="status"></span>
                                    </div>
                                @endif
                            </div> --}}
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

        @include('parts.discord')

        {{-- Let's draw all airline modals here --}}
        @foreach($modalAirports as $airport)
            @foreach($airport->airlines as $airline)
                @include('search.parts.flightsModal', ['primaryAirport' => $primaryAirport, 'airport' => $airport, 'airline' => $airline, 'filterByAircrafts' => $filterByAircrafts])
            @endforeach
        @endforeach

        @include('layouts.legend')
    </div>
@endsection

@section('js')    
    <script>
        // Get the show more button and add on click event where it removes the class that hides the rows
        var showMoreBtn = document.querySelector('#showMoreBtn')
        if(showMoreBtn){
            document.querySelector('#showMoreBtn').addEventListener('click', function() {
                expandAllRows();
            });

            // Expand all rows if user has clicked the table thead th's
            document.querySelectorAll('thead > tr > th').forEach(function(element) {
                element.addEventListener('click', function() {
                    expandAllRows();
                });
            });

            // Function to expand all rows
            var expanded = false
            function expandAllRows() {
                if(!expanded) {
                    document.querySelectorAll('.showmore-hidden').forEach(function(element) {
                        element.classList.remove('showmore-hidden');
                    });
                    document.querySelector('#showMoreRow').remove();
                    expanded = true;
                }
            }
        }

        // Randomise spinner
        var button = document.getElementById('randomiseBtn');
        if(button){
            button.addEventListener('click', function() {
                button.setAttribute('disabled', '')
                button.innerHTML = 'Randomise&nbsp;&nbsp;<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>'
                document.getElementById('form').submit()
            });
        }
            
    </script>

    @include('scripts.measures')
    @include('scripts.tooltip')
    @include('scripts.taf')
@endsection