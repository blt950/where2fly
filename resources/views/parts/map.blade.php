<aside id="map"></aside>

@if(session('efbpro') === null)
    @if(!Auth::user() || (Auth::user() && Auth::user()->lists->count() == 0))
        @if(!Route::is('search') && !Route::is('top'))
            <div class="hint">
                <i class="fa-sharp fa-lightbulb-on"></i>
                @guest
                    Create an account to fill the map with your own scenery list.
                @endguest
                @auth
                    Create your first scenery list to fill the map.
                @endauth
            </div>
        @endif
    @endif

    <div class="feedback">
        <i class="fa-sharp fa-box-ballot"></i>
        <b>Vote for the next feature!</b>
        <br>
        <a href="https://discord.gg/UkFg9Yy4gP" target="_blank">Check out #general in our Discord</a>
    </div>
@endif
