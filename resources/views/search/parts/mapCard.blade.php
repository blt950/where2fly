<div class="popup-card" data-airport="{{ $airport->icao }}">

    <div>
        <img class="flag border-0" src="/img/flags/{{ strtolower($airport->iso_country) }}.svg" height="16" data-bs-toggle="tooltip" data-bs-title="{{ getCountryName($airport->iso_country) }}" alt="Flag of {{ getCountryName($airport->iso_country) }}"></img>
        {{ $airport->icao }}
    </div>
    <h2>{{ $airport->name }}</h2>

    <dl class="font-kanit">

        <dt>Runways</dt>
        @foreach($airport->runways->where('closed', false) as $runway)
            <dd>
                <strong>{{ $runway->le_ident }}/{{ $runway->he_ident }}:</strong>
                {{ Illuminate\Support\Number::format($runway->length_ft, locale: 'de') }}ft
                <span class="text-white-50">({{ Illuminate\Support\Number::format(round($runway->length_ft * .3048), locale: 'de') }}m)</span>
            </dd>
        @endforeach

        

        

        <dt>Weather</dt>
        <dd>
            <div class="d-flex justify-content-between nav nav-pills" style="font-size: 0.75rem">
                <div class="d-flex">
                    <div>
                        <button class="nav-link light active" id="home-tab-{{ $airport->id }}" data-bs-toggle="tab" data-bs-target="#metar-pane-{{ $airport->id }}" type="button" role="tab">METAR</button>
                    </div>
                    <div>
                        <button class="nav-link light" data-bs-toggle="tab" data-bs-target="#taf-pane-{{ $airport->id }}" data-taf-button="true" data-airport-icao="{{ $airport->icao }}" type="button" role="tab">TAF</button>
                    </div>
                </div>
            </div>
            
            <div class="tab-content">
                {{-- METAR tab --}}
                <div class="tab-pane fade show active" id="metar-pane-{{ $airport->id }}" role="tabpanel" aria-labelledby="home-tab-{{ $airport->id }}" tabindex="0">{{ \Carbon\Carbon::parse($airport->metar->last_update)->format('dHi\Z') }} {{ $airport->metar->metar }}</div>
                
                {{-- TAF tab --}}
                @isset($tafs[$airport->icao])
                    <div class="tab-pane fade" id="taf-pane-{{ $airport->id }}" role="tabpanel" tabindex="0">{{ $tafs[$airport->icao] }}</div>
                @else
                    <div class="tab-pane fade" id="taf-pane-{{ $airport->id }}" role="tabpanel" tabindex="0">
                        <span class="spinner-border spinner-border-sm" role="status"></span>
                    </div>
                @endif
            </div>
        </dd>

        @if($airport->airlines->isNotEmpty())
            <dt>Airlines</dt>
            <dd>
                @foreach($airport->airlines as $airline)

                    @php $highlight = $airport->flights->where('airline_icao', $airline->icao_code)->contains(fn($flight) => $flight->aircrafts->whereIn('icao', $filterByAircrafts)->isNotEmpty()); @endphp
                    <button type="button" class="airline-button {{ $highlight ? 'highlight' : null }}" data-toggle-flights="{{ $primaryAirport->icao . '-' . $airport->icao . '-' . $airline->icao_code }}">
                        <img
                            data-bs-toggle="tooltip"
                            data-bs-title="See all {{ $airline->name }} flights"
                            class="airline-logo" 
                            src="{{ asset('img/airlines/'.$airline->iata_code.'.png') }}"
                        >
                    </button>
                @endforeach
            </dd>
        @endif
    </dl>

    <a class="btn btn-outline-primary btn-sm font-work-sans" href="https://windy.com/{{ $airport->icao }}" target="_blank">
        Windy <i class="fas fa-up-right-from-square"></i>
    </a>

    @php
        $simbriefUrl = 'orig=' . ($direction == 'departure' ? $primaryAirport->icao : $airport->icao ) . '&dest=' . ($direction == 'departure' ? $airport->icao : $primaryAirport->icao);
    @endphp
    <a class="btn btn-outline-primary btn-sm font-work-sans" href="https://dispatch.simbrief.com/options/custom?{{ $simbriefUrl }}" target="_blank">
        SimBrief <i class="fas fa-up-right-from-square"></i>
    </a>

</div>