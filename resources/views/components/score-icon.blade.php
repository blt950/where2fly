<span
    class="score-icon position-relative{{ $uncertain ? ' score-uncertain' : '' }}"
    data-bs-html="true"
    data-bs-toggle="tooltip"
    data-bs-title="{!! implode('<br>', $tooltipLines) !!}"
>
    <i class="{{ $highlighted ? 'text-success ' : '' }}fa-sharp {{ $scoreType['icon'] }}"></i>
    @if($probabilityBadge)
        <i class="fa-sharp fa-dot position-absolute probability {{ $highlighted ? 'text-success ' : '' }}" aria-hidden="true"></i>
    @endif
    @if($facilityDots->count())
        @if($facilityDots->count() == 1)
            <i class="fa-sharp fa-circle-quarter-stroke position-absolute atc {{ $highlighted ? 'text-success ' : '' }}" aria-hidden="true"></i>
        @elseif($facilityDots->count() == 2)
            <i class="fa-sharp fa-circle-half-stroke position-absolute atc {{ $highlighted ? 'text-success ' : '' }}" aria-hidden="true"></i>
        @elseif($facilityDots->count() == 3)
            <i class="fa-sharp fa-circle-three-quarters-stroke position-absolute atc {{ $highlighted ? 'text-success ' : '' }}" aria-hidden="true"></i>
        @elseif($facilityDots->count() > 3)
            <i class="fa-sharp fa-circle position-absolute atc {{ $highlighted ? 'text-success ' : '' }}" aria-hidden="true"></i>
        @endif
    @endif
</span>
