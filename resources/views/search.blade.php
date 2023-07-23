@extends('layouts.app')

@section('title', 'Results')
@section('content')

<div class="cover-container d-flex w-100 h-100 p-3 mx-auto flex-column">

    @include('layouts.menu')
  
    <main>
        <h1 class="mb-3 mt-5">Search Results</h1>

        @if($suggestedDeparture)
            <div class="d-flex flex-wrap justify-content-between">
                <h2>Departure suggestion</h2>

                {{-- Add possiblity to re-post the search query for a new random departure --}}
                <form method="POST" action="{{ ($wasAdvancedSearch) ? route('search.advanced') : route('search') }}">
                    @csrf

                    @foreach($_POST as $key => $value)
                        @if(is_array($value))
                            @foreach($value as $subvalue)
                                <input type="hidden" name="{{ $key }}[]" value="{{ $subvalue }}">
                            @endforeach
                        @else
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endforeach

                    <button class="btn btn-sm btn-warning mb-1" style="font-size: 1rem;"><i class="fas fa-shuffle"></i> Randomise</button>
                </form>

            </div>
        @else
            <h2>Departure</h2>
        @endif
        <div class="departure-container">
            <dl>
                <dt>Airport<dt>
                <dd>
                    <img class="flag" src="/img/flags/{{ strtolower($departure->iso_country) }}.svg" height="16" data-bs-toggle="tooltip" data-bs-title="{{ getCountryName($departure->iso_country) }}" alt="Flag of {{ getCountryName($departure->iso_country) }}"></img>
                    {{ $departure->icao }}
                </dd>
                <dd>{{ $departure->name }}</dd>
            </dl>

            <dl>
                <dt>Runway<dt>
                <dd class="rwy-feet">{{ $departure->longestRunway() }}</dd>
                <dd class="rwy-meters text-muted">{{ round($departure->longestRunway()* .3048) }}</dd>
            </dl>

            <dl>
                <dt>State<dt>
                @foreach($departure->scores as $score)
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

            <div style="width: 60%">
                <div class="d-flex mb-3 nav nav-pills" style="font-size: 0.75rem">
                    <div>
                        <button class="nav-link active" id="home-tab-{{ $departure->id }}" data-bs-toggle="tab" data-bs-target="#metar-pane-{{ $departure->id }}" type="button" role="tab">METAR</button>
                    </div>
                    <div>
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#taf-pane-{{ $departure->id }}" data-taf-button="true" data-airport-icao="{{ $departure->icao }}" type="button" role="tab">TAF</button>
                    </div>
                </div>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="metar-pane-{{ $departure->id }}" role="tabpanel" aria-labelledby="home-tab-{{ $departure->id }}" tabindex="0">{{ \Carbon\Carbon::parse($departure->metar->last_update)->format('dHi\Z') }} {{ $departure->metar->metar }}</div>
                    @isset($tafs[$departure->icao])
                        <div class="tab-pane fade" id="taf-pane-{{ $departure->id }}" role="tabpanel" tabindex="0">{{ $tafs[$departure->icao] }}</div>
                    @else
                        <div class="tab-pane fade" id="taf-pane-{{ $departure->id }}" role="tabpanel" tabindex="0">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <h2>Arrival suggestions</h2>
        <div class="scroll-fade">
            <div class="table-responsive">
                <table class="table table-hover text-start">
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

                        @if( ! $wasAdvancedSearch && isset($suggestedAirports->first()->scores) && $suggestedAirports->first()->scores->count() == 0 )

                            <tr>
                                <th class="text-center text-danger" colspan="9">
                                    <i class="fas fa-exclamation-triangle"></i> No airports matched your criteria
                                </th>
                            </tr>

                        @endif

                        @foreach($suggestedAirports as $airport)
                            <tr class="{{ ($count > 10) ? 'showmore-hidden' : null }}">
                                <th scope="row">{{ $count }}</th>
                                <td>{{ $airport->icao }}</td>
                                <td>{{ $airport->name }}</td>
                                <td><img class="flag" src="/img/flags/{{ strtolower($airport->iso_country) }}.svg" height="16" data-bs-toggle="tooltip" data-bs-title="{{ getCountryName($airport->iso_country) }}" alt="Flag of {{ getCountryName($airport->iso_country) }}"></img></td>
                                <td>{{ $airport->distance }}nm</td>
                                <td>{{ gmdate('G:i', floor($airport->airtime * 3600)) }}h</td>
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
                                    <i class="fas fa-exclamation-triangle"></i> No results matched your criteria
                                </th>
                            </tr>
                        @endif
                        <tr id="showMoreRow">
                            <th colspan="9" class="text-center text-danger">
                                <button id="showMoreBtn" class="btn btn-secondary">Show more</button>
                            </th>
                        </tr>
                    </tbody>
                </table>

            </div>

            <div class="alert alert-discord">
                <p class="text-white mb-0">
                    <i class="fa-brands fa-discord"></i> Contribute with suggestions, bug reports and vote on new features in <a class="text-white" href="https://discord.gg/UkFg9Yy4gP" target="_blank">our Discord <i class="fas fa-up-right-from-square"></i></a>
                </p>
            </div>
        </div>

        @include('layouts.legend')

    </main>

    <script>
        // Get the show more button and add on click event where it removes the class that hides the rows
        document.querySelector('#showMoreBtn').addEventListener('click', function() {
            document.querySelectorAll('.showmore-hidden').forEach(function(element) {
                element.classList.remove('showmore-hidden');
            });
            document.querySelector('#showMoreRow').remove();
        });
    </script>

    @include('scripts.measures')
    @include('scripts.tooltip')
    @include('scripts.taf')
  
    @include('layouts.footer')
</div>

@endsection