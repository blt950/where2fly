import './sentry';

import './bootstrap';
import '../sass/app.scss';

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

// Other
import '@u-elements/u-tabs';
import '@u-elements/u-datalist';
import '@u-elements/u-combobox';

// Metrics
document.querySelectorAll('a[href^="http"]').forEach(link => {
    // Check if the link is not pointing to your own domain
    if (link.hostname !== window.location.hostname) {
        link.addEventListener('click', function () {
            // Only get the domain of the link to avoid sending the full URL
            let url = new URL(link.href);
            let domain = url.hostname;

            if(window.umami){
                umami.track('External Link Click', {
                    url: domain
                });
            }

        });
    }
});