@php
    $scoreType = App\Http\Controllers\ScoreController::$score_types[$score->reason];
    $airport = $airport ?? null;

    // Tooltip: the description line (with PROB percentage folded in), then the
    // per-source detail — for VATSIM_ATC, all booked positions with their windows
    $tooltipLines = [e($scoreType['desc']) . (isset($score->data['probability']) ? ', ' . e($score->data['probability']) . '% probability' : '')];
    $bookingScores = ($score->reason === 'VATSIM_ATC' && $airport) ? $airport->atcBookingScores() : collect();
    $bookedFacilities = ($score->reason === 'VATSIM_ATC' && $airport) ? $airport->atcBookedFacilities() : collect();

    if ($score->reason === 'VATSIM_ATC' && $airport) {
        $liveAtc = $airport->scores->first(fn ($s) => $s->reason === 'VATSIM_ATC' && $s->source === App\Models\AirportScore::SOURCE_VATSIM);
        if ($liveAtc && $liveAtc->tooltipText()) {
            $tooltipLines[] = e($liveAtc->tooltipText());
        }
        if ($bookingScores->count()) {
            $tooltipLines[] = '<b>Bookings</b>';
            foreach ($bookingScores as $bookingScore) {
                $tooltipLines[] = e($bookingScore->tooltipText());
            }
        }
    } elseif ($score->tooltipText()) {
        $tooltipLines[] = e($score->tooltipText());
    }
@endphp
<span class="score-icon position-relative">
    <i
        class="{{ ($highlighted ?? false) ? 'text-success ' : '' }}fa-sharp {{ $scoreType['icon'] }}"
        data-bs-html="true"
        data-bs-toggle="tooltip"
        data-bs-title="{!! implode('<br>', $tooltipLines) !!}"
    ></i>
    @if($bookedFacilities->count())
        <span class="score-facility-dots" aria-hidden="true">
            @foreach($bookedFacilities as $facility)
                <span class="facility-dot--{{ strtolower($facility) }}"></span>
            @endforeach
        </span>
    @endif
</span>
