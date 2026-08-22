<footer class="mt-auto text-white-50">
    <div>
        {{ Config('app.version') }}
        | Created by <a href="https://blt950.com" target="_blank" class="text-white-50 text-decoration-underline">Blt950</a>
        | <a href="{{ route('changelog') }}" class="text-white-50 text-decoration-underline">Changelog</a>
        | <a href="{{ route('privacy') }}" class="text-white-50 text-decoration-underline">Privacy Policy</a>
        | <a href="{{ route('api') }}" class="text-white-50 text-decoration-underline">API</a>
    </div>
    {{-- Filled in by the map, and only on pages that have one. Its contents track whichever
         sources are actually being drawn. --}}
    <div id="map-attribution"></div>
</footer>