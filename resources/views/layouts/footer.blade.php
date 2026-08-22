<footer class="mt-auto text-white-50">
    <div>
        {{ Config('app.version') }}
        | Created by <a href="https://blt950.com" target="_blank" class="text-white-50 text-decoration-underline">Blt950</a>
        | <a href="{{ route('changelog') }}" class="text-white-50 text-decoration-underline">Changelog</a>
        | <a href="{{ route('privacy') }}" class="text-white-50 text-decoration-underline">Privacy Policy</a>
        | <a href="{{ route('api') }}" class="text-white-50 text-decoration-underline">API</a>
    </div>
    <div>
        Map powered by <a class="text-white-50 text-decoration-underline" href="https://maplibre.org/" target="_blank">MapLibre</a> &amp; <a class="text-white-50 text-decoration-underline" href="https://carto.com/attribution" target="_blank">CARTO</a>, &copy; <a class="text-white-50 text-decoration-underline" href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a> contributors
        
    </div>
</footer>