<div class="popup-card" data-flights="{{ $primaryAirport->icao . '-' . $airport->icao . '-' . $airline->icao_code }}">

    <h2>
        <img src="{{asset('img/airlines/'.$airline->iata_code.'.png')}}" height="17"> {{ $airline->name }} flights
    </h2>
    <ul class="list-unstyled">
        @foreach($airport->flights->where('airline_icao', $airline->icao_code)->sortByDesc('last_seen_at') as $flight)
            <li class="{{$flight->aircrafts->whereIn('icao', $filterByAircrafts)->count() > 0 ? 'highlight' : null}}">
                {{ $flight->flight_icao }}
                ({{ $flight->aircrafts->pluck('icao')->implode(',') }})
                {{ $flight->last_seen_at->diffForHumans() }}
            </li>
        @endforeach
    </ul>
</div>