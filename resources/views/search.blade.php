@extends('layouts.app')

@section('title', 'Results')
@section('content')

<div class="cover-container d-flex w-100 h-100 p-3 mx-auto flex-column">

    @include('layouts.menu')
  
    <main>
        <h1 class="mb-3 mt-5">Suggestion Results</h1>

        <p class="d-block d-sm-none">Scroll to the sides to see the whole table</p>

        <div class="table-responsive">
            <table class="table table-hover bg-white text-start">
                <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">ICAO</th>
                        <th scope="col">Airport</th>
                        <th scope="col">Country</th>
                        <th scope="col">Distance</th>
                        <th scope="col" width="10%">Air Time</th>
                        <th scope="col" width="12%">Why</th>
                        <th scope="col">Runway</th>
                        <th scope="col" width="40%">Details</th>
                    </tr>
                </thead>
                <tbody>
                    @php $count = 1; @endphp
                    @foreach($suggestedAirports as $airport)
                        <tr>
                            <th scope="row">{{ $count }}</th>
                            <td>{{ $airport->icao }}</td>
                            <td>{{ $airport->name }}</td>
                            <td><img class="flag" src="/img/flags/{{ strtolower($airport->iso_country) }}.svg" height="16" data-bs-toggle="tooltip" data-bs-title="{{ getCountryName($airport->iso_country) }}" alt="Flag of {{ getCountryName($airport->iso_country) }}"></img></td>
                            <td>{{ $distances[$airport->icao] }}nm</td>
                            <td>{{ $airtimes[$airport->icao] }}h</td>
                            <td class="fs-5">
                                @foreach($airport->scores as $score)
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
                            </td>
                            <td>
                                <div class="rwy-feet">{{ $airport->longestRunway() }}</div>
                                <div class="rwy-meters text-black text-opacity-50">{{ round($airport->longestRunway()* .3048) }}</div>
                            </td>
                            <td>
                                <div class="d-flex mb-3 nav nav-pills" style="font-size: 0.75rem">
                                    <div>
                                        <button class="nav-link active" id="home-tab-{{ $airport->id }}" data-bs-toggle="tab" data-bs-target="#metar-pane-{{ $airport->id }}" type="button" role="tab">METAR</button>
                                    </div>
                                    <div>
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#taf-pane-{{ $airport->id }}" data-taf-button="true" data-airport-icao="{{ $airport->icao }}" type="button" role="tab">TAF</button>
                                    </div>
                                    <div class="hover-show ms-auto">
                                        <a class="btn btn-sm float-end font-work-sans text-muted" href="https://www.simbrief.com/system/dispatch.php?orig={{ $departure->icao }}&dest={{ $airport->icao }}" target="_blank">
                                            <span class="d-none d-lg-inline d-xl-inline">SimBrief</span> <i class="fas fa-up-right-from-square"></i>
                                        </a>
                                    </div>
                                </div>
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
                                <i class="fas fa-exclamation-triangle"></i> No results matched your criteria
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