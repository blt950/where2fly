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
    
    <div class="filterbox">
        <span class="m-0">Filter: 
            <a class="btn btn-sm {{ Route::is('top') ? 'btn-primary' : 'btn-secondary' }}" href="{{ route('top') }}">All</a>
            <a class="btn btn-sm {{ $continent == 'AF' ? 'btn-primary' : 'btn-secondary' }}" href="{{ route('top.filtered', 'AF') }}">Africa</a>
            <a class="btn btn-sm {{ $continent == 'AS' ? 'btn-primary' : 'btn-secondary' }}" href="{{ route('top.filtered', 'AS') }}">Asia</a>
            <a class="btn btn-sm {{ $continent == 'EU' ? 'btn-primary' : 'btn-secondary' }}" href="{{ route('top.filtered', 'EU') }}">Europe</a>
            <a class="btn btn-sm {{ $continent == 'NA' ? 'btn-primary' : 'btn-secondary' }}" href="{{ route('top.filtered', 'NA') }}">North America</a>
            <a class="btn btn-sm {{ $continent == 'OC' ? 'btn-primary' : 'btn-secondary' }}" href="{{ route('top.filtered', 'OC') }}">Oceania</a>
            <a class="btn btn-sm {{ $continent == 'SA' ? 'btn-primary' : 'btn-secondary' }}" href="{{ route('top.filtered', 'SA') }}">South America</a>
        </span>

        <span>VATSIM: 
            <a class="btn btn-sm {{ $exclude === null ? 'btn-primary' : 'btn-secondary' }}" href="{{ route(Route::currentRouteName(), $continent) }}">Include</a>
            <a class="btn btn-sm {{ $exclude == 'vatsim' ? 'btn-primary' : 'btn-secondary' }}" href="{{ route(Route::currentRouteName(), $continent) }}?exclude=vatsim">Exclude</a>
        </span>
    </div>
        
    <div class="scroll-fade">
        <div class="table-responsive">
            <table class="table table-hover text-start sortable asc">
                <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Airport</th>
                        <th scope="col" width="10%">Conditions</th>
                        <th scope="col">Runway</th>
                        <th scope="col" class="no-sort" width="50%">Details</th>
                    </tr>
                </thead>
                <tbody>
                    @php $count = 1; @endphp
                    @foreach($airports as $airport)
                        <tr>
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

                                    <div class="d-flex">
                                        <div class="hover-show">
                                            <a class="btn btn-sm float-end font-work-sans text-muted d-none d-lg-inline d-xl-inline" href="{{ route('front', ['icao' => $airport->icao]) }}">
                                                <span>Arrival</span> <i class="fas fa-search"></i>
                                            </a>
                                        </div>
                                        <div class="hover-show">
                                            <a class="btn btn-sm float-end font-work-sans text-muted d-none d-lg-inline d-xl-inline" href="{{ route('front.departures', ['icao' => $airport->icao]) }}">
                                                <span>Departure</span> <i class="fas fa-search"></i>
                                            </a>
                                        </div>
                                        <div class="hover-show secondary">
                                            <a class="btn btn-sm float-end font-work-sans text-muted" href="https://windy.com/{{ $airport->icao }}" target="_blank">
                                                <span class="d-none d-lg-inline d-xl-inline">Windy</span> <i class="fas fa-up-right-from-square"></i>
                                            </a>
                                        </div>
                                        <div class="hover-show">
                                            <a class="btn btn-sm float-end font-work-sans text-muted" href="https://dispatch.simbrief.com/options/custom?dest={{ $airport->icao }}" target="_blank">
                                                <span class="d-none d-lg-inline d-xl-inline">SimBrief</span> <i class="fas fa-up-right-from-square"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="tab-content">
                                    <div class="tab-pane fade show active" id="metar-pane-{{ $airport->id }}" role="tabpanel" aria-labelledby="home-tab-{{ $airport->id }}" tabindex="0">{{ \Carbon\Carbon::parse($airport->metar->last_update)->format('dHi\Z') }} {{ $airport->metar->metar }}</div>
                                    @isset($tafs[$airport->icao])
                                        <div class="tab-pane fade" id="taf-pane-{{ $airport->id }}" role="tabpanel" tabindex="0">{{ $tafs[$airport->icao] }}</div>
                                    @else
                                        <div class="tab-pane fade" id="taf-pane-{{ $airport->id }}" role="tabpanel" tabindex="0">
                                            <span class="spinner-border spinner-border-sm" role="status"></span>
                                        </div>
                                    @endif
                                </div>
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
    </div>

    @include('layouts.legend')
@endsection

@section('js')
    @include('scripts.measures')
    @include('scripts.tooltip')
    @include('scripts.taf')
@endsection