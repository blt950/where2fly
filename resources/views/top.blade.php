@extends('layouts.app')

@section('title', 'Search')
@section('content')

<div class="cover-container d-flex w-100 h-100 p-3 mx-auto flex-column">

    @include('layouts.menu')
  
    <main class="px-3">
        <h1 class="mb-3 mt-5">Top Airports Right Now</h1>
        <p>Filter: 
            <a class="btn {{ Route::is('top') ? 'btn-info' : 'btn-secondary' }}" href="{{ route('top') }}">All</a>
            <a class="btn {{ $continent == 'AF' ? 'btn-info' : 'btn-secondary' }}" href="{{ route('top.filtered', 'AF') }}">Africa</a>
            <a class="btn {{ $continent == 'AS' ? 'btn-info' : 'btn-secondary' }}" href="{{ route('top.filtered', 'AS') }}">Asia</a>
            <a class="btn {{ $continent == 'EU' ? 'btn-info' : 'btn-secondary' }}" href="{{ route('top.filtered', 'EU') }}">Europe</a>
            <a class="btn {{ $continent == 'NA' ? 'btn-info' : 'btn-secondary' }}" href="{{ route('top.filtered', 'NA') }}">North America</a>
            <a class="btn {{ $continent == 'OC' ? 'btn-info' : 'btn-secondary' }}" href="{{ route('top.filtered', 'OC') }}">Oceania</a>
            <a class="btn {{ $continent == 'SA' ? 'btn-info' : 'btn-secondary' }}" href="{{ route('top.filtered', 'SA') }}">South America</a>
        </p>
        <table class="table table-hover bg-white text-start">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">ICAO</th>
                    <th scope="col">Airport</th>
                    <th scope="col">Country</th>
                    <th scope="col">Why</th>
                    <th scope="col">Runway</th>
                    <th scope="col">Details</th>
                </tr>
            </thead>
            <tbody>
                @php $count = 1; @endphp
                @foreach($airports as $airport)
                    <tr>
                        <th scope="row">{{ $count }}</th>
                        <td>{{ $airport->icao }}</td>
                        <td>{{ $airport->name }}</td>
                        <td>{{ $airport->iso_country }}</td>
                        <td class="fs-5">
                            @foreach($airport->scores as $score)
                                <i class="fas {{ App\Http\Controllers\ScoreController::$score_types[$score->reason]['icon'] }}" title="{{ App\Http\Controllers\ScoreController::$score_types[$score->reason]['desc'] }}"></i>
                            @endforeach
                        </td>
                        <td>{{ $airport->longestRunway() }}ft</td>
                        <td>
                            <ul class="nav nav-pills mb-3" style="font-size: 0.75rem" id="pills-tab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="home-tab" data-bs-toggle="tab" data-bs-target="#metar-pane-{{ $airport->id }}" type="button" role="tab">METAR</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#taf-pane-{{ $airport->id }}" data-taf-button="true" data-airport-icao="{{ $airport->icao }}" type="button" role="tab">TAF</button>
                                </li>
                            </ul>
                            <div class="tab-content" id="myTabContent">
                                <div class="tab-pane fade show active" id="metar-pane-{{ $airport->id }}" role="tabpanel" tabindex="0">{{ $airport->metar->metar }}</div>
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
            </tbody>
        </table>
    </main>

    @include('scripts.taf')
  
    @include('layouts.footer')
</div>

@endsection