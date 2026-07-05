<span
    class="score-icon position-relative"
    data-bs-html="true"
    data-bs-toggle="tooltip"
    data-bs-title="{!! implode('<br>', $tooltipLines) !!}"
>
    <i class="{{ $highlighted ? 'text-success ' : '' }}fa-sharp {{ $scoreType['icon'] }}"></i>
    @if($probabilityBadge)
        <i class="fa-sharp fa-question position-absolute score-probability-badge" aria-hidden="true"></i>
    @endif
    @if($facilityDots->count())
        <span class="score-facility-dots" aria-hidden="true">
            @foreach($facilityDots as $facility)
                <span class="facility-dot  {{ $highlighted ? 'bg-success ' : '' }}"></span>
            @endforeach
        </span>
    @endif
</span>
