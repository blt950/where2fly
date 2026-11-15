import { useCallback, useEffect, useRef } from 'react';
import { router } from '@inertiajs/react';

import { selectedAirportIn, withSelectedAirport } from './selection';

// Selecting an airport writes the URL; the URL drives the map. Opening a card adds a history
// entry, so Back closes it; switching airports or closing replaces the entry instead.
export const useSelection = (pageUrl, state, actions) => {
    const selected = selectedAirportIn(pageUrl);
    const currentUrl = useRef(pageUrl);
    currentUrl.current = pageUrl;

    const select = useCallback((icao) => {
        const current = selectedAirportIn(currentUrl.current);
        const next = icao || null;

        if (next === current) {
            return;
        }

        const method = current === null ? 'push' : 'replace';
        router[method]({ url: withSelectedAirport(currentUrl.current, next), preserveState: true, preserveScroll: true });
    }, []);

    // Also re-applied after a new scene, which starts with nothing focused. No selection closes
    // the cards as well, as their close button does.
    useEffect(() => {
        if ((state.focusAirport ?? null) !== selected) {
            actions.set(selected ? { focusAirport: selected } : { focusAirport: null, showAirportIdCard: null });
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [selected, state.sceneVersion]);

    return select;
};
