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
                    <th scope="col">Why</th>
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
                        <td class="fs-5">
                            @foreach($airport->scores as $score)
                                <i class="fas {{ App\Http\Controllers\ScoreController::$score_types[$score->reason]['icon'] }}" title="{{ App\Http\Controllers\ScoreController::$score_types[$score->reason]['desc'] }}"></i>
                            @endforeach
                        </td>
                        <td>
                            <ul class="nav nav-pills mb-3" style="font-size: 0.75rem" id="pills-tab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="home-tab" data-bs-toggle="tab" data-bs-target="#home-tab-pane" type="button" role="tab" aria-controls="home-tab-pane" aria-selected="true">METAR</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile-tab-pane" type="button" role="tab" aria-controls="profile-tab-pane" aria-selected="false">TAF</button>
                                </li>
                            </ul>
                            <div class="tab-content" id="myTabContent">
                                <div class="tab-pane fade show active" id="home-tab-pane" role="tabpanel" aria-labelledby="home-tab" tabindex="0">{{ $airport->metar->metar }}</div>
                                <div class="tab-pane fade" id="profile-tab-pane" role="tabpanel" aria-labelledby="profile-tab" tabindex="0">TAF {{ $airport->metar->metar }}</div>
                                <div class="tab-pane fade" id="contact-tab-pane" role="tabpanel" aria-labelledby="contact-tab" tabindex="0">...</div>
                                <div class="tab-pane fade" id="disabled-tab-pane" role="tabpanel" aria-labelledby="disabled-tab" tabindex="0">...</div>
                            </div>
                        </td>
                    </tr>
                    @php $count++; @endphp
                @endforeach
            </tbody>
        </table>
    </main>
  
    @include('layouts.footer')
</div>

@endsection