@extends('layouts.app')

@section('title', 'Search')
@section('content')

<div class="cover-container d-flex w-100 h-100 p-3 mx-auto flex-column">

    @include('layouts.menu')
  
    <main class="px-3">
        <h1 class="mb-3 mt-5">Suggestion Results</h1>
        <table class="table table-hover bg-white text-start">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">ICAO</th>
                    <th scope="col">Airport</th>
                    <th scope="col">Country</th>
                    <th scope="col">Distance</th>
                    <th scope="col">Air Time</th>
                    <th scope="col">Why</th>
                    <th scope="col">Runway</th>
                    <th scope="col">Details</th>
                </tr>
            </thead>
            <tbody>
                @php $count = 1; @endphp
                @foreach($suggestedAirports as $airport)
                    <tr>
                        <th scope="row">{{ $count }}</th>
                        <td>{{ $airport->icao }}</td>
                        <td>{{ $airport->name }}</td>
                        <td>{{ $airport->iso_country }}</td>
                        <td>{{ $distances[$airport->icao] }}nm</td>
                        <td>{{ $airtimes[$airport->icao] }}h</td>
                        <td class="fs-5">
                            @foreach($airport->scores as $score)
                                <i class="fas {{ App\Http\Controllers\ScoreController::$score_types[$score->reason]['icon'] }}" title="{{ App\Http\Controllers\ScoreController::$score_types[$score->reason]['desc'] }}"></i>
                            @endforeach
                        </td>
                        <td>
                            {{ $airport->longestRunway() }}ft
                        </td>
                        <td>
                            <ul class="nav nav-pills mb-3" style="font-size: 0.75rem" id="pills-tab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="home-tab" data-bs-toggle="tab" data-bs-target="#metar-pane-{{ $airport->id }}" type="button" role="tab" aria-controls="metar-pane-{{ $airport->id }}" aria-selected="true">METAR</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#taf-pane-{{ $airport->id }}" type="button" role="tab" aria-controls="taf-pane-{{ $airport->id }}" aria-selected="false">TAF</button>
                                </li>
                            </ul>
                            <div class="tab-content" id="myTabContent">
                                <div class="tab-pane fade show active" id="metar-pane-{{ $airport->id }}" role="tabpanel" aria-labelledby="home-tab" tabindex="0">{{ $airport->metar->metar }}</div>
                                <div class="tab-pane fade" id="taf-pane-{{ $airport->id }}" role="tabpanel" aria-labelledby="profile-tab" tabindex="0">{{ $tafs[$airport->icao] }}</div>
                            </div>
                        </td>
                    </tr>
                    @php $count++; @endphp
                @endforeach
            </tbody>
        </table>
    </main>

    <script>

    </script>
  
    @include('layouts.footer')
</div>

@endsection