<div class="popup-card" data-card-id="{{ $airport->icao }}-scenery">

    <div class="d-flex justify-content-between">
        <h2>Scenery</h2>
        <button class="btn-close" aria-label="Close scenery card" data-card-event="close" data-card-type="scenery" data-card-for="{{ $airport->icao }}-scenery"></button>
    </div>

    @if(!$sceneries || !$sceneries->where('icao', $airport->icao)->count())
        <p>No scenery available</p>
    @else
        @foreach($sceneries->where('icao', $airport->icao) as $scenery)
            <a href="{{ $scenery->link }}" class="d-block btn btn-outline-light font-work-sans text-start mt-2" target="_blank">
                <span class="badge bg-blue">
                    {{ $scenery->simulator->shortened_name }}
                </span>
                @if($scenery->payware == -1)
                    <span class="badge bg-danger">Included</span>
                @elseif($scenery->payware == 0)
                    <span class="badge bg-success">Freeware</span>
                @else
                    <span class="badge bg-info">Payware</span>
                @endif

                &nbsp;{{ $scenery->author }} <i class="fas fa-up-right-from-square float-end pt-1"></i>
            </a>
        @endforeach
    @endif

    <a href="{{ route('scenery.create', ['airport' => $airport->icao]) }}" class="btn btn-outline-primary btn-sm font-work-sans mt-2" target="_blank">
        <i class="fas fa-plus"></i> Add missing scenery
    </a>
</div>