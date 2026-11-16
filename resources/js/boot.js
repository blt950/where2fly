// What the page needs before React: Sentry, Bootstrap, the stylesheet, the custom elements.
import './sentry';

import './bootstrap';
import '../sass/app.scss';

import '@u-elements/u-tabs';
import '@u-elements/u-datalist';
import '@u-elements/u-combobox';

import { watchKeyboard } from './keyboard';

watchKeyboard();

// Metrics: delegated, so links rendered later by React are tracked as well. Only the domain is
// sent, never the full URL.
document.addEventListener('click', (event) => {
    const link = event.target.closest?.('a[href^="http"]');

    if (link && link.hostname !== window.location.hostname && window.umami) {
        umami.track('External Link Click', { url: new URL(link.href).hostname });
    }
});
