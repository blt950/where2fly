<div class="d-block d-sm-none">
    <ul class="ps-0" style="font-size: 0.7em; list-style-type: none;">
        @foreach(\App\Http\Controllers\ScoreController::$score_types as $s)
            <li>
                <i class="fa {{ $s['icon'] }}"></i>
                {{ $s['desc'] }}
                &nbsp;
            </li>
        @endforeach
    </ul>
</div>