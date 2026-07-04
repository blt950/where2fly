@php
    $scoreType = App\Http\Controllers\ScoreController::$score_types[$score->reason];
    $airport = $airport ?? null;

    // Tooltip: description, probability on its own line, then the per-source
    // detail — for VATSIM_ATC the online facilities (no logoff time, we don't
    // know it) and every booked position with its window
    $tooltipLines = [e($scoreType['desc'])];
    if (isset($score->data['probability'])) {
        $tooltipLines[] = e($score->data['probability']) . '% probability';
    }

    $facilityDots = collect();
    if ($score->reason === 'VATSIM_ATC' && $airport) {
        $onlineFacilities = $airport->atcOnlineFacilities();
        $bookingScores = $airport->atcBookingScores();
        $facilityDots = $airport->atcFacilities();

        if ($onlineFacilities->count()) {
            $tooltipLines[] = '<b>Online</b>';
            $tooltipLines[] = e($onlineFacilities->join(', '));
        }
        if ($bookingScores->count()) {
            $tooltipLines[] = '<b>Bookings</b>';
            foreach ($bookingScores as $bookingScore) {
                $tooltipLines[] = e($bookingScore->tooltipText());
            }
        }
        if ($onlineFacilities->isEmpty() && $bookingScores->isEmpty() && $score->tooltipText()) {
            $tooltipLines[] = e($score->tooltipText());
        }
    } elseif ($score->tooltipText()) {
        $tooltipLines[] = e($score->tooltipText());
    }
@endphp
<span
    class="score-icon position-relative"
    data-bs-html="true"
    data-bs-toggle="tooltip"
    data-bs-title="{!! implode('<br>', $tooltipLines) !!}"
>
    <i class="{{ ($highlighted ?? false) ? 'text-success ' : '' }}fa-sharp {{ $scoreType['icon'] }}"></i>
    @if(isset($score->data['probability']))
        <i class="fa-sharp fa-circle-question position-absolute score-probability-badge" aria-hidden="true"></i>
    @endif
    @if($facilityDots->count())
        <span class="score-facility-dots" aria-hidden="true">
            @foreach($facilityDots as $facility)
                <span class="facility-dot--{{ strtolower($facility) }}"></span>
            @endforeach
        </span>
    @endif
</span>
