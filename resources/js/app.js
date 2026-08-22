import './sentry';

import './bootstrap';
import '../sass/app.scss';

if (document.getElementById('map')) {
    import('./components/Map');
}

// Other
import '@u-elements/u-tabs';
import '@u-elements/u-datalist';
import '@u-elements/u-combobox';

// Metrics
document.querySelectorAll('a[href^="http"]').forEach(link => {
    // Check if the link is not pointing to your own domain
    if (link.hostname !== window.location.hostname) {
        link.addEventListener('click', function (e) {
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