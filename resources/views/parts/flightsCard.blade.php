<div class="popup-card" data-card-id="{{ $primaryAirport->icao . '-' . $airport->icao . '-' . $airline->icao_code }}">

    <div class="d-flex justify-content-between">
        <h2>
            <img class="airline-logo small" alt="{{ $airline->name }} logo" src="{{asset('img/airlines/'.$airline->iata_code.'.png')}}"> {{ $airline->name }} flights
        </h2>

        <button class="btn-close" aria-label="Close flights card" data-card-event="close" data-card-type="flights" data-card-for="{{ $primaryAirport->icao . '-' . $airport->icao . '-' . $airline->icao_code }}"></button>
    </div>

    <ul class="list-unstyled">
        @foreach($airport->flights->where('airline_icao', $airline->icao_code)->sortByDesc('last_seen_at') as $flight)
            <li class="{{$flight->aircrafts->whereIn('icao', $filterByAircrafts)->count() > 0 ? 'text-success' : null}}">
                {{ $flight->flight_icao }}
                ({{ $flight->aircrafts->pluck('icao')->implode(',') }})
                {{ $flight->last_seen_at->diffForHumans() }}
            </li>
        @endforeach
    </ul>
</div>