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
                    <a class="nav-link {{ Route::is('scenery*') ? 'active' : '' }}" href="{{ route('scenery') }}">
                        <i class="fas fa-map"></i>&nbsp;
                        Scenery
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ Route::is('donate') ? 'active' : '' }}" href="{{ route('donate') }}">
                        <i class="fas fa-donate"></i>&nbsp;
                        Donate
                    </a>
                </li>
                @if(session('efbpro') === null)
                    @guest
                        <li class="nav-item">
                            <a class="nav-link {{ Route::is('login') || Route::is('register') ? 'active' : '' }}" href="{{ route('login') }}">
                                <i class="fas fa-lock"></i>&nbsp;
                                Login
                            </a>
                        </li>
                    @endguest
                    @auth
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle {{ Route::is('user.account') ? 'active' : '' }}" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                @if(isset($hasNotifications) && $hasNotifications)
                                    <i class="fas fa-bell text-warning"></i>&nbsp;
                                @else
                                    <i class="fas fa-user"></i>&nbsp;
                                @endif
                                {{ Auth::user()->username }}
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                <li><a class="dropdown-item" href="{{ route('user.account') }}">Settings</a></li>
                                <li><a class="dropdown-item" href="{{ route('list.index') }}">My Lists</a></li>
                                @can('showAdmin', App\Models\User::class)
                                    <li>
                                        <a class="dropdown-item" href="{{ route('admin') }}">
                                            Admin
                                            @if(isset($hasNotifications) && $hasNotifications)
                                                <span class="badge bg-warning text-dark">{{ $hasNotifications }}</span>
                                            @endif
                                        </a>
                                    </li>
                                @endcan
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="{{ route('user.logout') }}">Logout</a></li>
                            </ul>
                        </li>
                    @endauth
                @endif
                <li class="nav-item">
                    <a class="nav-link" href="https://discord.gg/UkFg9Yy4gP" target="_blank">
                        <i class="fab fa-discord"></i>
                        <span class="d-lg-none">&nbsp;Discord</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="https://github.com/blt950/where2fly" target="_blank">
                        <i class="fab fa-github"></i>
                        <span class="d-lg-none">&nbsp;GitHub</span>
                    </a>
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