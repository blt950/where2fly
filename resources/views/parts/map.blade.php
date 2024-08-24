<aside>
    <div id="map" class="map"></div>

    @if(!Auth::user() || (Auth::user() && Auth::user()->lists->count() == 0))
        @if(!Route::is('search') && !Route::is('top'))
            <div class="hint">
                <i class="fas fa-lightbulb-on"></i>
                @guest
                    Create an account to fill the map with your own scenery list.
                @endguest
                @auth
                    Create your first scenery list to fill the map.
                @endauth
            </div>
        @endif
    @endif
</aside>