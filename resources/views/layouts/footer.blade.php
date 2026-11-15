<footer class="mt-auto text-white-50">
    <div>
        {{ $nav['version'] }}
        | Created by <a href="https://blt950.com" target="_blank" class="text-white-50 text-decoration-underline">Blt950</a>
        @foreach($nav['info'] as $item)
            | <x-nav-link :item="$item" class="text-white-50 text-decoration-underline" />
        @endforeach
    </div>
    {{-- Filled in by the map, and only on pages that have one. Its contents track whichever
         sources are actually being drawn. --}}
    <div id="map-attribution"></div>
</footer>