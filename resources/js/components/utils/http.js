import { captureException } from '@sentry/react';

// The page's token. The shell refreshes the meta tag after every response (inertia.jsx), so a
// session that expired and restarted doesn't leave every later request failing with a 419.
export const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

export const getJson = (url) => fetch(url, {
    credentials: 'include',
    headers: { 'Accept': 'application/json' },
});

export const postJson = (url, body) => fetch(url, {
    method: 'POST',
    credentials: 'include',
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
    },
    body: JSON.stringify(body),
});

export const reportError = (error) => {
    captureException(error);
    console.error(error.message);
};
