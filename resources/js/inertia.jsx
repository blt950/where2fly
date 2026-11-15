import './boot';

import { createElement } from 'react';
import { createRoot } from 'react-dom/client';
import { createInertiaApp, router } from '@inertiajs/react';
import { captureException, ErrorBoundary } from '@sentry/react';

import AppShell from './components/shell/AppShell';
import { MapFallback } from './components/map/MapView';

const pages = import.meta.glob('./pages/**/*.jsx');

let lastVisit = null;
router.on('start', (event) => {
    if (!event.detail.visit.prefetch) {
        lastVisit = event.detail.visit;
    }
});

router.on('httpException', (event) => {
    const { response } = event.detail;

    // The server's own error pages (pages/Error.jsx) render as pages; only crashes are worth a report
    if (response.headers['x-inertia']) {
        if (response.status >= 500) {
            captureException(new Error(`Inertia visit failed (${response.status})`));
        }

        return;
    }

    // Anything else (a proxy's 502, maintenance mode) gets a full page load, so the browser shows it
    // as a page instead of Inertia's raw-HTML modal. Maintenance (503) is planned: one report per
    // visitor per navigation during a deploy would bury the real ones.
    if (response.status !== 503) {
        captureException(new Error(`Inertia visit got a non-Inertia response (${response.status})`));
    }
    event.preventDefault();
    window.location.assign(lastVisit?.method === 'get' ? String(lastVisit.url) : window.location.href);
});
router.on('networkError', (event) => captureException(event.detail.exception ?? new Error('Inertia network error')));

// fetch() callers read the meta tag; keep it current after a session restart. Only server responses
// (`success`), never a restored history entry, which carries the token of its time.
router.on('success', (event) => {
    const token = event.detail.page.props.csrfToken;

    if (token) {
        document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', token);
    }
});

createInertiaApp({
    // Same format as the Blade header's <title>.
    title: (title) => (title ? `Where2Fly | ${title}` : 'Where2Fly'),
    resolve: async (name) => {
        const page = (await pages[`./pages/${name}.jsx`]()).default;

        // One persistent layout for every page: it owns the map, so the map outlives visits.
        page.layout ??= (child) => createElement(AppShell, null, child);

        return page;
    },
    setup({ el, App, props }) {
        // The phone CSS addresses the layout as body's direct flex child; the root is that child.
        el.className = 'd-flex w-100 h-100 mx-auto flex-column';

        createRoot(el).render(
            <ErrorBoundary fallback={<MapFallback />}>
                <App {...props} />
            </ErrorBoundary>,
        );
    },
    progress: { color: '#ddb81c' },
});
