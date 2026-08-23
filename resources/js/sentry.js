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
