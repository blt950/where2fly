<header class="mb-auto">
    <nav class="navbar navbar-expand-lg navbar-dark">
        <a href="{{ route('front') }}" class="navbar-brand">
            <h3 class="mb-0 logo">Where2Fly</h3>
        </a>
        <button class="navbar-toggler collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu" aria-controls="navbarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarMenu">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link {{ Route::is('front*') || Route::is('search*') ? 'active' : '' }}" href="{{ route('front') }}">Search</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ Route::is('top*') ? 'active' : '' }}" href="{{ route('top') }}">Top List</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ Route::is('changelog') ? 'active' : '' }}" href="{{ route('changelog') }}">Changelog</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="https://forms.gle/wsP3s322LTP6oJog8" target="_blank">Feedback</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="https://discord.gg/UkFg9Yy4gP" target="_blank">Discord</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" target="_blank">Donate</a>
                </li>
            </ul>
        </div>
        <div id="menu-overlay" class="menu-overlay d-md-none"></div>
    </nav>
</header>
@if(Config::get('app.env') != "production")
    <span class="testbadge badge bg-danger  ms-2 mt-2" role="alert">
        TEST
    </span>
@endif