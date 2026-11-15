{{-- One NavigationHelper item as a link; resources/js/components/shell/NavItem.jsx is its React twin --}}
@props(['item'])
<a href="{{ $item['href'] }}" {{ $attributes }} @if($item['active']) aria-current="page" @endif @if($item['external']) target="_blank" rel="noopener" @endif>{{ $slot->isEmpty() ? $item['label'] : $slot }}</a>
