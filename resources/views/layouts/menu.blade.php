<header class="mb-auto">
    <nav class="navbar navbar-expand-lg navbar-dark">
        <a href="{{ route('front') }}" class="navbar-brand mb-0 logo">
            Where2Fly
        </a>
        <button class="navbar-toggler collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu" aria-controls="navbarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarMenu">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link {{ Route::is('front*') || Route::is('search*') ? 'active' : '' }}" href="{{ route('front') }}">
                        <i class="fas fa-search"></i>&nbsp;
                        Search
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ Route::is('top*') ? 'active' : '' }}" href="{{ route('top') }}">
                        <i class="fas fa-list"></i>&nbsp;
                        Top List
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="https://discord.gg/UkFg9Yy4gP" target="_blank">
                        <i class="fab fa-discord"></i>&nbsp;
                        Discord
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ Route::is('donate') ? 'active' : '' }}" href="#" target="_blank">
                        <i class="fas fa-donate"></i>&nbsp;
                        Donate
                    </a>
                </li>
                @guest
                    <li class="nav-item">
                        <a class="nav-link {{ Route::is('login') || Route::is('register') ? 'active' : '' }}" href="{{ route('login') }}">
                            <i class="fas fa-lock"></i>&nbsp;
                            Login
                        </a>
                    </li>
                @endguest
                @auth
                    <li class="nav-item">
                        <a class="nav-link {{ Route::is('user.account') ? 'active' : '' }}" href="{{ route('user.account') }}">
                            <i class="fas fa-user"></i>&nbsp;
                            {{ Auth::user()->username }}
                        </a>
                    </li>
                @endauth
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