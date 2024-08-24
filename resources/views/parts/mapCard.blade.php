<div class="popup-card" data-card-id="{{ $airport->icao }}">

    <div>
        <img class="flag border-0" src="/img/flags/{{ strtolower($airport->iso_country) }}.svg" height="16" data-bs-toggle="tooltip" data-bs-title="{{ getCountryName($airport->iso_country) }}" alt="Flag of {{ getCountryName($airport->iso_country) }}"></img>
        {{ $airport->icao }}
    </div>
    <h2>{{ $airport->name }}</h2>

    <dl class="font-kanit">

        <dt>Runways</dt>
        @foreach($airport->runways->where('closed', false)->whereNotNull('length_ft') as $runway)
            <dd>
                <strong>{{ $runway->le_ident }}/{{ $runway->he_ident }}:</strong>
                {{ Illuminate\Support\Number::format($runway->length_ft, locale: 'en_US') }}ft
                <span class="text-white-50">({{ Illuminate\Support\Number::format(round($runway->length_ft * .3048), locale: 'en_US') }}m)</span>
            </dd>
        @endforeach

        <dt>METAR</dt>
        <dd>
            @if($airport->metar)
                {{ \Carbon\Carbon::parse($airport->metar->last_update)->format('dHi\Z') }} {{ $airport->metar->metar }}
            @else
                Not Available
            @endif
        </dd>

        <dt>TAF</dt>
        <dd>
            <button class="btn btn-outline-light btn-sm" data-airport-icao="{{ $airport->icao }}" data-taf-button="true">Fetch</button>
        </dd>

        @if($airport->airlines && $airport->airlines->isNotEmpty())
            <dt>Airlines</dt>
            <dd>
                @foreach($airport->airlines as $airline)

                    @php $highlight = $airport->flights->where('airline_icao', $airline->icao_code)->contains(fn($flight) => $flight->aircrafts->whereIn('icao', $filterByAircrafts)->isNotEmpty()); @endphp
                    <button type="button" class="airline-button {{ $highlight ? 'highlight' : null }}" data-card-event="open" data-card-type="flights" data-card-for="{{ $primaryAirport->icao . '-' . $airport->icao . '-' . $airline->icao_code }}">
                        <img
                            data-bs-toggle="tooltip"
                            data-bs-title="See all {{ $airline->name }} flights"
                            class="airline-logo" 
                            src="{{ asset('img/airlines/'.$airline->iata_code.'.png') }}"
                            alt="See all {{ $airline->name }} flights"
                        >
                    </button>
                @endforeach
            </dd>
        @endif
    </dl>

    <div class="d-flex flex-wrap gap-2">

        <button class="btn btn-outline-primary btn-sm font-work-sans" data-card-event="open" data-card-type="scenery" data-card-for="{{ $airport->icao }}-scenery">
            <i class="fas fa-map"></i> Scenery
        </button>

        <a class="btn btn-outline-primary btn-sm font-work-sans" href="https://windy.com/{{ $airport->icao }}" target="_blank">
            Windy <i class="fas fa-up-right-from-square"></i>
        </a>
    
        @isset($direction)
            @php
                $simbriefUrl = 'orig=' . ($direction == 'departure' ? $primaryAirport->icao : $airport->icao ) . '&dest=' . ($direction == 'departure' ? $airport->icao : $primaryAirport->icao);
            @endphp
            <a class="btn btn-outline-primary btn-sm font-work-sans" href="https://dispatch.simbrief.com/options/custom?{{ $simbriefUrl }}" target="_blank">
                SimBrief <i class="fas fa-up-right-from-square"></i>
            </a>
        @else
            <a class="btn btn-outline-primary btn-sm font-work-sans" href="{{ route('front', ['icao' => $airport->icao]) }}">
                <span>Arrival</span> <i class="fas fa-search"></i>
            </a>
        
            <a class="btn btn-outline-primary btn-sm font-work-sans" href="{{ route('front.departures', ['icao' => $airport->icao]) }}">
                <span>Departure</span> <i class="fas fa-search"></i>
            </a>

            <a class="btn btn-outline-primary btn-sm font-work-sans" href="https://dispatch.simbrief.com/options/custom?dest={{ $airport->icao }}" target="_blank">
                <span>SimBrief</span> <i class="fas fa-up-right-from-square"></i>
            </a>
        @endif        
    </div>

</div>