import './boot';

import { createElement } from 'react';
import { createRoot } from 'react-dom/client';
import { createInertiaApp, router } from '@inertiajs/react';
import { captureException, ErrorBoundary } from '@sentry/react';

import AppShell from './components/shell/AppShell';
import { MapFallback } from './components/map/MapView';

const pages = import.meta.glob('./pages/**/*.jsx');

router.on('httpException', (event) => captureException(new Error(`Inertia visit failed (${event.detail.response.status})`)));
router.on('networkError', (event) => captureException(event.detail.exception ?? new Error('Inertia network error')));

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
