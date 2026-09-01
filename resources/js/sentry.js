import { init, setUser } from '@sentry/react'

const meta = (name) => document.querySelector(`meta[name="${name}"]`)?.content
const dsn = meta('sentry-dsn')

if (dsn) {
    init({
        dsn,
        release: meta('sentry-release'),
        environment: meta('sentry-environment'),

        tracesSampleRate: 0,

        ignoreErrors: [
            'ResizeObserver loop limit exceeded',
            'ResizeObserver loop completed with undelivered notifications',
            'AbortError',
            'The operation was aborted',
            /^Failed to fetch( \(.+\))?$/,
            /^Load failed( \(.+\))?$/,
            /^NetworkError when attempting to fetch resource\.( \(.+\))?$/,
            'Script error.',
            /\[Cloudflare Turnstile\] Error: (300|600)\d{3}/,
            // Chunk load failures (flaky network, stale bundle after deploy) — app.js already shows a reload fallback
            /^Importing a module script failed/,
            /error loading dynamically imported module/,
        ],

        denyUrls: [
            /beacon\.min\.js/i,
            /static\.cloudflareinsights\.com/i,
            /extensions\//i,
            /^chrome:\/\//i,
            /^chrome-extension:\/\//i,
            /^moz-extension:\/\//i,
            /^safari-web-extension:\/\//i,
        ],
    })

    const userId = meta('sentry-user-id')

    if (userId) {
        setUser({ id: userId })
    }
}
