<header class="mb-auto">
    <div>
        <a href="{{ route('front') }}" class="text-decoration-none text-white">
            <h3 class="float-md-start mb-0 logo">Where2Fly</h3>
        </a>
        
        <nav class="nav nav-masthead justify-content-center float-md-end">
            <a class="nav-link {{ Route::is('front') || Route::is('front.advanced') || Route::is('search') ? 'active' : '' }}" href="{{ route('front') }}">Search</a>
            <a class="nav-link {{ Route::is('top*') ? 'active' : '' }}" href="{{ route('top') }}">Top List</a>
            <a class="nav-link {{ Route::is('changelog') ? 'active' : '' }}" href="{{ route('changelog') }}">Changelog</a>
            <a class="nav-link" href="https://forms.gle/wsP3s322LTP6oJog8" target="_blank">Feedback</a>
        </nav>
    </div>
</header>