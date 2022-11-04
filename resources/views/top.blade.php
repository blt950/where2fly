@extends('layouts.app')

@section('title', 'Top List')
@section('content')

<div class="cover-container d-flex w-100 h-100 p-3 mx-auto flex-column">

    @include('layouts.menu')
  
    <main>
        <h1 class="mb-3 mt-5">Top Airports Right Now</h1>
        <p>Filter: 
            <a class="btn btn-sm {{ Route::is('top') ? 'btn-primary' : 'btn-secondary' }}" href="{{ route('top') }}">All</a>
            <a class="btn btn-sm {{ $continent == 'AF' ? 'btn-primary' : 'btn-secondary' }}" href="{{ route('top.filtered', 'AF') }}">Africa</a>
            <a class="btn btn-sm {{ $continent == 'AS' ? 'btn-primary' : 'btn-secondary' }}" href="{{ route('top.filtered', 'AS') }}">Asia</a>
            <a class="btn btn-sm {{ $continent == 'EU' ? 'btn-primary' : 'btn-secondary' }}" href="{{ route('top.filtered', 'EU') }}">Europe</a>
            <a class="btn btn-sm {{ $continent == 'NA' ? 'btn-primary' : 'btn-secondary' }}" href="{{ route('top.filtered', 'NA') }}">North America</a>
            <a class="btn btn-sm {{ $continent == 'OC' ? 'btn-primary' : 'btn-secondary' }}" href="{{ route('top.filtered', 'OC') }}">Oceania</a>
            <a class="btn btn-sm {{ $continent == 'SA' ? 'btn-primary' : 'btn-secondary' }}" href="{{ route('top.filtered', 'SA') }}">South America</a>
        </p>
            
        <p class="d-block d-sm-none">Scroll to the sides to see the whole table</p>
        
        <div class="table-responsive">
            <table class="table table-hover bg-white text-start">
                <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">ICAO</th>
                        <th scope="col">Airport</th>
                        <th scope="col">Country</th>
                        <th scope="col" width="10%">Why</th>
                        <th scope="col">Runway</th>
                        <th scope="col" width="50%">Details</th>
                    </tr>
                </thead>
                <tbody>
                    @php $count = 1; @endphp
                    @foreach($airports as $airport)
                        <tr>
                            <th scope="row">{{ $count }}</th>
                            <td>{{ $airport->icao }}</td>
                            <td>{{ $airport->name }}</td>
                            <td><img class="flag" src="/img/flags/{{ strtolower($airport->iso_country) }}.svg" height="16" data-bs-toggle="tooltip" data-bs-title="{{ getCountryName($airport->iso_country) }}" alt="Flag of {{ getCountryName($airport->iso_country) }}"></img></td>
                            <td class="fs-5">
                                @foreach($airport->scores as $score)
                                    <i 
                                        class="fas {{ App\Http\Controllers\ScoreController::$score_types[$score->reason]['icon'] }}"
                                        data-bs-html="true"
                                        data-bs-toggle="tooltip"
                                        data-bs-title="{{ App\Http\Controllers\ScoreController::$score_types[$score->reason]['desc'] }}<br>{{ $score->data }}"
                                    ></i>
                                @endforeach
                            </td>
                            <td>
                                <div class="rwy-feet">{{ $airport->longestRunway() }}</div>
                                <div class="rwy-meters text-black text-opacity-50">{{ round($airport->longestRunway()* .3048) }}</div>
                            </td>
                            <td>
                                <ul class="nav nav-pills mb-3" style="font-size: 0.75rem" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="home-tab-{{ $airport->id }}" data-bs-toggle="tab" data-bs-target="#metar-pane-{{ $airport->id }}" type="button" role="tab">METAR</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#taf-pane-{{ $airport->id }}" data-taf-button="true" data-airport-icao="{{ $airport->icao }}" type="button" role="tab">TAF</button>
                                    </li>
                                </ul>
                                <div class="tab-content">
                                    <div class="tab-pane fade show active" id="metar-pane-{{ $airport->id }}" role="tabpanel" aria-labelledby="home-tab-{{ $airport->id }}" tabindex="0">{{ \Carbon\Carbon::parse($airport->metar->last_update)->format('dHm\Z') }} {{ $airport->metar->metar }}</div>
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

        @include('layouts.legend')

    </main>

    @include('scripts.measures')
    @include('scripts.tooltip')
    @include('scripts.taf')
  
    @include('layouts.footer')
</div>

@endsection