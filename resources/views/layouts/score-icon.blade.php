@php
    $scoreType = App\Http\Controllers\ScoreController::$score_types[$score->reason];
@endphp
<span class="score-icon position-relative">
    <i
        class="{{ ($highlighted ?? false) ? 'text-success ' : '' }}fa-sharp {{ $scoreType['icon'] }}"
        data-bs-html="true"
        data-bs-toggle="tooltip"
        data-bs-title="{{ $scoreType['desc'] }}@if($score->tooltipText())<br>{{ $score->tooltipText() }}@endif"
    ></i>
    @if(isset($score->data['probability']))
        <i
            class="fa-sharp fa-circle-question position-absolute score-probability-badge"
            data-bs-toggle="tooltip"
            data-bs-title="{{ $score->data['probability'] }}% chance"
        ></i>
    @endif
</span>
