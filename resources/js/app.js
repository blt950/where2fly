import './boot';

const mapElement = document.getElementById('map');

if (mapElement) {
    // Vite rethrows chunk preload failures as unhandled rejections unless cancelled.
    window.addEventListener('vite:preloadError', (e) => e.preventDefault());

    import('./components/Map').catch(() => {
        mapElement.className = 'map map-error d-flex flex-column align-items-center justify-content-center text-center p-4';
        mapElement.innerHTML = `
            <p class="mb-1"><i class="fa-sharp fa-triangle-exclamation" aria-hidden="true"></i> The map could not be loaded</p>
            <p class="mb-3">Check your connection and try reloading the page.</p>
            <button type="button" class="btn btn-primary" data-map-reload>Reload page</button>`;
        mapElement.querySelector('[data-map-reload]').addEventListener('click', () => window.location.reload());
    });
}
