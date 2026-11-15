import { useEffect, useState } from 'react';
import { captureException } from '@sentry/react';

// One request per URL and body, shared by everyone who asks: a drawer tab's count and the card
// behind it use the same response. A failed request is dropped from the cache, so it can retry.
const cache = new Map();

export const cachedPost = (url, body, { emptyOn404 = false } = {}) => {
    const key = `${url} ${JSON.stringify(body)}`;

    if (!cache.has(key)) {
        const request = fetch(url, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            },
            body: JSON.stringify(body),
        })
            .then((response) => {
                if (response.status === 404 && emptyOn404) return {};
                if (!response.ok) throw new Error(`Request to ${url} failed (${response.status})`);

                return response.json().then((json) => json.data ?? {});
            })
            .catch((error) => {
                cache.delete(key);
                throw error;
            });

        cache.set(key, request);
    }

    return cache.get(key);
};

export const clearRequestCache = () => cache.clear();

// `body` null (or `enabled` false) means nothing to ask yet
export const useCachedPost = (url, body, { enabled = true, emptyOn404 = false } = {}) => {
    const [data, setData] = useState(null);
    const key = body ? JSON.stringify(body) : null;

    useEffect(() => {
        if (!key || !enabled) {
            return undefined;
        }

        let live = true;
        setData(null);

        cachedPost(url, JSON.parse(key), { emptyOn404 })
            .then((loaded) => { if (live) setData(loaded); })
            .catch((error) => {
                captureException(error);
                console.error(error.message);
            });

        return () => { live = false; };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [url, key, enabled]);

    return data;
};
