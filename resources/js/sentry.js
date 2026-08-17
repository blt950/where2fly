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
            // A fetch that never reached the server is a network condition, not a bug.
            // Anchored so genuine application TypeErrors still report.
            /^Failed to fetch$/,
            /^Load failed$/,
            /^NetworkError when attempting to fetch resource\.$/,
            'Script error.',
        ],

        denyUrls: [
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
