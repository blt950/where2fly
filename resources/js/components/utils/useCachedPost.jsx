import { useEffect, useState } from 'react';

import { postJson, reportError } from './http';

// One request per URL and body, shared by everyone who asks: a drawer tab's count and the card
// behind it use the same response. A failed request is dropped from the cache, so it can retry.
// Entries expire, since the airport card carries the weather.
const MAX_AGE_MS = 5 * 60 * 1000;
const cache = new Map();

const keyOf = (url, body) => `${url} ${JSON.stringify(body)}`;

const fresh = (key) => {
    const entry = cache.get(key);

    if (entry && Date.now() - entry.at > MAX_AGE_MS) {
        cache.delete(key);
        return undefined;
    }

    return entry;
};

export const cachedPost = (url, body, { emptyOn404 = false } = {}) => {
    const key = keyOf(url, body);

    if (!fresh(key)) {
        const entry = { at: Date.now(), value: undefined };

        entry.request = postJson(url, body)
            .then((response) => {
                if (response.status === 404 && emptyOn404) return {};
                if (!response.ok) throw new Error(`Request to ${url} failed (${response.status})`);

                return response.json().then((json) => json.data ?? {});
            })
            .then((value) => {
                entry.value = value;
                return value;
            })
            .catch((error) => {
                cache.delete(key);
                throw error;
            });

        cache.set(key, entry);
    }

    return cache.get(key).request;
};

// What an earlier request already answered, for a first render without a loading flash
const settled = (url, body) => (body ? fresh(keyOf(url, body))?.value ?? null : null);

export const clearRequestCache = () => cache.clear();

// `body` null (or `enabled` false) means nothing to ask yet
export const useCachedPost = (url, body, { enabled = true, emptyOn404 = false } = {}) => {
    const key = body && enabled ? keyOf(url, body) : null;
    // Kept with the request it answers, so a render for another request never shows it
    const [loaded, setLoaded] = useState(null);

    useEffect(() => {
        if (!key) {
            return undefined;
        }

        let live = true;

        cachedPost(url, body, { emptyOn404 })
            .then((data) => { if (live) setLoaded({ key, data }); })
            .catch(reportError);

        return () => { live = false; };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [key]);

    if (!key) {
        return null;
    }

    return loaded?.key === key ? loaded.data : settled(url, body);
};
