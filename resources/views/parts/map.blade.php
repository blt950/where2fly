<aside id="map"></aside>


@if(!Route::is('search') && !Route::is('top'))
    @if(!Auth::user())
        <div class="hint">
            <i class="fa-sharp fa-lightbulb-on"></i>
            Create an account to fill the map with your own scenery list.
        </div>
    @else
        @empty(Auth::user()->feedback_last_read_number)
            <div class="hint hint-feedback">
                <i class="fa-sharp fa-box-ballot"></i>
                Have your say on what get built next on our new feedback page.
            </div>
        @else
            @if(Auth::user()->lists->count() == 0)
                <div class="hint">
                    <i class="fa-sharp fa-lightbulb-on"></i>
                    Create a scenery list to fill the map with your own data.
                </div>
            @endif
        @endempty
    @endif
@endif

<!--
<div class="feedback">
    <i class="fa-sharp fa-box-ballot"></i>
    <b>Have your say on what get built next</b>
    <br>
    <a href="{{ route('feedback') }}">Check out our new Feedback page</a>
</div>
-->