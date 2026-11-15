import { useEffect, useState } from 'react';
import { captureException } from '@sentry/react';

// One request per airport, shared by the scenery card and the count on its tab, so opening the
// tab after seeing the count costs nothing. A failed request is dropped, so it can be retried.
const cache = new Map();

export const loadScenery = (icao) => {
    if (!cache.has(icao)) {
        const request = fetch(route('api.airport.scenery'), {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            },
            body: JSON.stringify({ airportIcao: icao }),
        })
            // The endpoint answers 404 when an airport has no sceneries: that's an empty result
            .then((response) => {
                if (response.status === 404) return {};
                if (!response.ok) throw new Error(`Could not load sceneries (${response.status})`);

                return response.json().then((body) => body.data ?? {});
            })
            .catch((error) => {
                cache.delete(icao);
                throw error;
            });

        cache.set(icao, request);
    }

    return cache.get(icao);
};

export const clearSceneryCache = () => cache.clear();

export const useScenery = (icao, enabled = true) => {
    const [data, setData] = useState(null);

    useEffect(() => {
        if (!icao || !enabled) {
            return undefined;
        }

        let live = true;
        setData(null);

        loadScenery(icao)
            .then((loaded) => { if (live) setData(loaded); })
            .catch((error) => {
                captureException(error);
                console.error(error.message);
            });

        return () => { live = false; };
    }, [icao, enabled]);

    return data;
};

// Distinct developers across every simulator: one scenery sold for two simulators counts once
export const sceneryCount = (data) => new Set(Object.values(data ?? {}).flat().map((item) => item.developer)).size;
