import './boot';

import { createElement } from 'react';
import { createRoot } from 'react-dom/client';
import { createInertiaApp, router } from '@inertiajs/react';
import { captureException, ErrorBoundary } from '@sentry/react';

import AppShell from './components/shell/AppShell';
import { restoreSelectionInPlace } from './components/shell/useSelection';
import { MapFallback } from './components/map/MapView';

const pages = import.meta.glob('./pages/**/*.jsx');

restoreSelectionInPlace();

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

// Shared props restored from history are as old as their entry. Its flash messages have been
// read; and if the login has changed since, the navigation, lists and map hint are asked for again.
const SESSION_PROPS = ['nav', 'alerts', 'mapHint', 'csrfToken', 'listsRevision'];
const accountIn = (page) => page.props.nav?.account?.label ?? null;
let account;

router.on('navigate', (event) => {
    const { page, type } = event.detail;

    // umami only watches pushState/replaceState: Back and Forward are page views it never sees.
    // Sent once <Head> has set the title.
    if (type === 'history') {
        setTimeout(() => window.umami?.track((props) => ({
            ...props, url: window.location.pathname + window.location.search, title: document.title,
        })), 300);
    }

    if (type !== 'history') {
        account = accountIn(page);
    } else if (account !== undefined && accountIn(page) !== account) {
        router.reload({ only: SESSION_PROPS });
    } else if (page.props.alerts?.messages?.length) {
        router.replace({
            props: (props) => ({ ...props, alerts: { ...props.alerts, messages: [] } }),
            preserveState: true,
            preserveScroll: true,
        });
    }
});

// Back onto a page the browser kept whole (bfcache), e.g. after logging out: same staleness
window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        router.reload({ only: SESSION_PROPS });
    }
});

createInertiaApp({
    // Same format as the Blade header's <title>.
    title: (title) => (title ? `Where2Fly | ${title}` : 'Where2Fly'),
    resolve: async (name) => {
        const load = pages[`./pages/${name}.jsx`];

        // A component name with no file is a typo in a controller's Inertia::render()
        if (!load) {
            throw new Error(`Inertia page "${name}" not found: no resources/js/pages/${name}.jsx`);
        }

        const page = (await load()).default;

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
