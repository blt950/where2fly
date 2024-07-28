<div class="modal fade" id="{{ $primaryAirport->icao . '-' . $airport->icao . '-' . $airline->icao_code }}-Modal" tabindex="-1" aria-labelledby="{{ $primaryAirport->icao . '-' . $airport->icao . '-' . $airline->icao_code }}-Modal-Label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="{{ $primaryAirport->icao . '-' . $airport->icao . '-' . $airline->icao_code }}-Modal-Label">
                    <img src="{{asset('img/airlines/'.$airline->iata_code.'.png')}}" height="17"> {{ $airline->name }}
                    <br>
                    {{ $primaryAirport->icao }} to {{ $airport->icao }} 
                </h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul class="list-unstyled">
                    @foreach($airport->flights->where('airline_icao', $airline->icao_code) as $flight)
                        <li>
                            {{ $flight->flight_icao }} ({{ $flight->aircrafts->pluck('icao')->implode(',') }}) {{ $flight->last_seen_at->diffForHumans() }}
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>