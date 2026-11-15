{{-- The header; links from NavigationHelper ($nav). resources/js/components/shell/Menu.jsx is its React twin --}}
@php
    $donate = collect($nav['community'])->firstWhere('key', 'donate');
    $social = collect($nav['community'])->where('key', '!=', 'donate');
    $feedback = $nav['feedback'];
    $account = $nav['account'];
@endphp
<header class="mb-auto">
    <nav class="navbar navbar-expand-xl navbar-dark">
        <div class="d-flex d-row">
            <a href="{{ $nav['main'][0]['href'] }}" class="d-block navbar-brand mb-0 logo">
                Where2Fly
            </a>
            <x-nav-link :item="$donate" class="donate btn btn-sm btn-light">
                <img src="/img/kofi_symbol.svg" alt="Coffee cup with a heart" />
                {{ $donate['label'] }}
            </x-nav-link>
        </div>
        <button class="navbar-toggler collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu" aria-controls="navbarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarMenu">
            <ul class="navbar-nav ms-auto">
                @foreach($nav['main'] as $item)
                    <li class="nav-item">
                        <x-nav-link :item="$item" @class(['nav-link', 'active' => $item['active']])>
                            <i class="fa-sharp {{ $item['icon'] }}"></i>&nbsp;
                            {{ $item['label'] }}
                        </x-nav-link>
                    </li>
                @endforeach

                <li class="nav-item">
                    <x-nav-link :item="$feedback" @class(['nav-link', 'active' => $feedback['active']])>
                        <i @class(['fa-sharp', $feedback['icon'], 'unread' => $feedback['unread']])></i>&nbsp;
                        {{ $feedback['label'] }}
                    </x-nav-link>
                </li>

                @if($nav['login'])
                    <li class="nav-item">
                        <x-nav-link :item="$nav['login']" @class(['nav-link', 'active' => $nav['login']['active']])>
                            <i class="fa-sharp {{ $nav['login']['icon'] }}"></i>&nbsp;
                            {{ $nav['login']['label'] }}
                        </x-nav-link>
                    </li>
                @endif

                @if($account)
                    <li class="nav-item dropdown">
                        <a @class(['nav-link', 'dropdown-toggle', 'active' => $account['active']]) href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            @if($account['notifications'])
                                <i class="fa-sharp fa-bell text-warning"></i>&nbsp;
                            @else
                                <i class="fa-sharp fa-user"></i>&nbsp;
                            @endif
                            {{ $account['label'] }}
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                            @foreach($account['items'] as $item)
                                @if($item['key'] === 'logout')
                                    <li><hr class="dropdown-divider"></li>
                                @endif
                                <li>
                                    <x-nav-link :item="$item" @class(['dropdown-item', 'active' => $item['active']])>
                                        {{ $item['label'] }}
                                        @if($item['key'] === 'admin' && $account['notifications'])
                                            <span class="badge bg-warning text-dark">{{ $account['notifications'] }}</span>
                                        @endif
                                    </x-nav-link>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                @endif

                @foreach($social as $item)
                    <li class="nav-item">
                        <x-nav-link :item="$item" class="nav-link">
                            <i class="{{ $item['icon'] }}"></i>
                            <span class="d-lg-none">&nbsp;{{ $item['label'] }}</span>
                        </x-nav-link>
                    </li>
                @endforeach
            </ul>
        </div>
        <div id="menu-overlay" class="menu-overlay d-md-none"></div>
    </nav>
</header>
@if(!app()->isProduction())
<span class="testbadge badge bg-danger  ms-2 mt-2" role="alert">
    TEST
</span>
@endif
