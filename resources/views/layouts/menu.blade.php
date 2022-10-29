<header class="mb-auto">
    <div>
        <a href="{{ route('front') }}" class="text-white">
            <h3 class="float-md-start mb-0">Where2Fly <span class="badge text-bg-secondary fs-6 pt-2">Beta 3</span></h3>
        </a>
        
        <nav class="nav nav-masthead justify-content-center float-md-end">
            <a class="nav-link {{ Route::is('front') || Route::is('search') ? 'active' : '' }}" href="{{ route('front') }}">Search</a>
            <a class="nav-link {{ Route::is('top') ? 'active' : '' }}" href="{{ route('top') }}">Top List</a>
            <a class="nav-link"href="/changelog.txt" target="_blank">Changelog</a>
            <a class="nav-link" href="https://forms.gle/wsP3s322LTP6oJog8" target="_blank">Feedback</a>
        </nav>
    </div>
</header>