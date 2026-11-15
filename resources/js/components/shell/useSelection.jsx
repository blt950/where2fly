import { useCallback, useEffect, useRef } from 'react';
import { router } from '@inertiajs/react';

import { selectedAirportIn, withSelectedAirport } from './selection';

// Marks a history entry that opening a card pushed. Kept in the entry's own props, which Inertia
// stores in history state, so it holds after Back and Forward too.
export const OPENED_HERE = 'selectionPushed';

// Selecting an airport writes the URL; the URL drives the map. Opening a card adds a history
// entry, so Back closes it; switching airports replaces the entry instead. Closing steps back off
// the entry opening added, so the next Back leaves the page instead of landing on the same one;
// a card that came with the URL (a shared link) has nothing to step back to and is replaced.
export const useSelection = (page, state, actions) => {
    const selected = selectedAirportIn(page.url);
    const current = useRef(page);
    current.current = page;

    const select = useCallback((icao) => {
        const { url, props } = current.current;
        const was = selectedAirportIn(url);
        const next = icao || null;

        if (next === was) {
            return;
        }

        const visit = { url: withSelectedAirport(url, next), preserveState: true, preserveScroll: true };

        if (was === null) {
            router.push({ ...visit, props: (currentProps) => ({ ...currentProps, [OPENED_HERE]: true }) });
        } else if (next === null && props?.[OPENED_HERE]) {
            window.history.back();
        } else {
            router.replace(visit);
        }
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
