<footer class="mt-auto text-white-50 d-flex flex-row justify-content-between">
    <div>
        {{ Config('app.version') }}
    </div>
    <div>
        Created by <a href="https://blt950.com" target="_blank" class="text-white-50 text-decoration-underline">Blt950</a> 
        | <a href="{{ route('privacy') }}" class="text-white-50 text-decoration-underline">Privacy Policy</a>
        | <a href="{{ route('api') }}" class="text-white-50 text-decoration-underline">API</a>
    </div>
</footer>