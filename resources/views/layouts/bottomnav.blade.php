{{-- The phone/tablet navigation for Blade pages; links from NavigationHelper ($nav). shell/BottomNav.jsx is its React twin --}}
@php
    $feedback = $nav['feedback'];
    $account = $nav['account'];
    $menuLabel = fn (array $item) => match ($item['key']) {
        'feedback' => $item['label'] . ($feedback['unread'] ? ' (new)' : ''),
        'settings' => "{$item['label']} ({$account['label']})",
        'admin' => $item['label'] . ($account['notifications'] ? " ({$account['notifications']})" : ''),
        default => $item['label'],
    };
    $sections = [
        [$feedback, ...($account['items'] ?? []), ...array_filter([$nav['login']])],
        $nav['community'],
        $nav['info'],
    ];
@endphp
<nav class="bottom-nav" aria-label="Main">
    @foreach($nav['main'] as $item)
        <x-nav-link :item="$item" class="bottom-nav-tab">
            <i class="fa-sharp {{ $item['icon'] }}" aria-hidden="true"></i><span>{{ $item['label'] }}</span>
        </x-nav-link>
    @endforeach

    <div class="dropup bottom-nav-more">
        <button type="button" class="bottom-nav-tab" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false" @if($nav['moreActive']) aria-current="page" @endif>
            <i class="fa-sharp {{ ($account['notifications'] ?? 0) || $feedback['unread'] ? 'fa-bell text-warning' : 'fa-bars' }}" aria-hidden="true"></i><span>More</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            @foreach($sections as $index => $section)
                @if($index > 0)
                    <li><hr class="dropdown-divider"></li>
                @endif
                @foreach($section as $item)
                    <li><x-nav-link :item="$item" @class(['dropdown-item', 'active' => $item['active']])>{{ $menuLabel($item) }}</x-nav-link></li>
                @endforeach
            @endforeach
            <li><span class="dropdown-item-text text-white-50 small">{{ $nav['version'] }}</span></li>
        </ul>
    </div>
</nav>
