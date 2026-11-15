{{-- The phone/tablet navigation for Blade pages: the twin of resources/js/components/shell/BottomNav.jsx --}}
@php
    $moreActive = Route::is('feedback*', 'user.account', 'list.*', 'admin', 'login', 'register', 'changelog', 'privacy', 'api');
    $item = fn (bool $active) => 'dropdown-item' . ($active ? ' active' : '');
@endphp
<nav class="bottom-nav" aria-label="Main">
    <a href="{{ route('front') }}" class="bottom-nav-tab" @if(Route::is('front*', 'search*')) aria-current="page" @endif>
        <i class="fa-sharp fa-search" aria-hidden="true"></i><span>Search</span>
    </a>
    <a href="{{ route('top') }}" class="bottom-nav-tab" @if(Route::is('top*')) aria-current="page" @endif>
        <i class="fa-sharp fa-list" aria-hidden="true"></i><span>Top List</span>
    </a>
    <a href="{{ route('scenery') }}" class="bottom-nav-tab" @if(Route::is('scenery', 'scenery.filtered')) aria-current="page" @endif>
        <i class="fa-sharp fa-map" aria-hidden="true"></i><span>Scenery</span>
    </a>

    <div class="dropup bottom-nav-more">
        <button type="button" class="bottom-nav-tab" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false" @if($moreActive) aria-current="page" @endif>
            <i class="fa-sharp {{ !empty($hasNotifications) || !empty($unreadFeedback) ? 'fa-bell text-warning' : 'fa-bars' }}" aria-hidden="true"></i><span>More</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="{{ $item(Route::is('feedback*')) }}" href="{{ route('feedback') }}">Feedback{{ !empty($unreadFeedback) ? ' (new)' : '' }}</a></li>
            @auth
                <li><a class="{{ $item(Route::is('user.account')) }}" href="{{ route('user.account') }}">Settings ({{ Auth::user()->username }})</a></li>
                <li><a class="{{ $item(Route::is('list.*')) }}" href="{{ route('list.index') }}">My Lists</a></li>
                @can('showAdmin', App\Models\User::class)
                    <li><a class="{{ $item(Route::is('admin')) }}" href="{{ route('admin') }}">Admin{{ !empty($hasNotifications) ? " ($hasNotifications)" : '' }}</a></li>
                @endcan
                <li><a class="dropdown-item" href="{{ route('user.logout') }}">Logout</a></li>
            @else
                <li><a class="{{ $item(Route::is('login', 'register')) }}" href="{{ route('login') }}">Login</a></li>
            @endauth
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="https://discord.gg/UkFg9Yy4gP" target="_blank" rel="noopener">Discord</a></li>
            <li><a class="dropdown-item" href="https://github.com/blt950/where2fly" target="_blank" rel="noopener">GitHub</a></li>
            <li><a class="dropdown-item" href="https://ko-fi.com/X7X21V3MEX" target="_blank" rel="noopener">Buy me a coffee</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="{{ route('changelog') }}">Changelog</a></li>
            <li><a class="dropdown-item" href="{{ route('privacy') }}">Privacy Policy</a></li>
            <li><a class="dropdown-item" href="{{ route('api') }}">API</a></li>
            <li><span class="dropdown-item-text text-white-50 small">{{ config('app.version') }}</span></li>
        </ul>
    </div>
</nav>
