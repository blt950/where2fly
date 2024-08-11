<footer class="mt-auto text-white-50 d-flex flex-row justify-content-between">
    <div>
        {{ Config('app.version') }}
        | Created by <a href="https://blt950.com" target="_blank" class="text-white-50 text-decoration-underline">Blt950</a>
        | <a href="{{ route('privacy') }}" class="text-white-50 text-decoration-underline">Privacy Policy</a>
        | <a href="{{ route('api') }}" class="text-white-50 text-decoration-underline">API</a>
    </div>
    <div>
        Map powered by <a class="text-white-50 text-decoration-underline" href="https://leafletjs.com/" target="_blank">Leaflet</a> & <a class="text-white-50 text-decoration-underline" href="https://cartodb.com/attribution" target="_blank">CartoDB</a>
        
    </div>
</footer>