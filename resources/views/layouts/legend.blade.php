<div class="d-block d-sm-none">
    <ul class="ps-0 mt-3" style="font-size: 0.7em; list-style-type: none;">
        @foreach(\App\Helpers\ScoreHelper::TYPES as $s)
            <li>
                <i class="fa-sharp {{ $s['icon'] }}"></i>
                {{ $s['desc'] }}
                &nbsp;
            </li>
        @endforeach
    </ul>
</div>