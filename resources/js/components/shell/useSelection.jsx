import { useCallback, useEffect } from 'react';
import { router } from '@inertiajs/react';

import { selectedAirportIn, withSelectedAirport } from './selection';

// Marks a history entry that opening a card pushed. Kept in the entry's own props, which Inertia
// stores in history state, so it holds after Back and Forward too.
export const OPENED_HERE = 'selectionPushed';
// Shared by a page's entry and the card entry pushed from it: the two are one page
export const ORIGIN = 'selectionOrigin';

const newOrigin = () => `${Date.now().toString(36)}${Math.random().toString(36).slice(2, 8)}`;

// The page as last rendered, for the handlers below: there is one shell, so one page
let livePage = null;

// Inertia restores a history entry as a new page, remounting it and dropping what was typed.
// Between a page and its card entry only the selection moves, so that step is made in place.
const onPopState = (event) => {
    const restored = event.state?.page;
    const origin = livePage?.props?.[ORIGIN];

    if (!origin || restored?.component !== livePage.component || restored.props?.[ORIGIN] !== origin) {
        return;
    }

    event.stopImmediatePropagation();
    router.replace({
        url: restored.url,
        props: (live) => ({ ...live, [OPENED_HERE]: Boolean(restored.props[OPENED_HERE]) }),
        preserveState: true,
        preserveScroll: true,
    });
};

// Call before the Inertia app is created: listeners on window run in the order they were added
export const restoreSelectionInPlace = () => window.addEventListener('popstate', onPopState);

// Opening a card pushes a history entry (Back closes it), switching replaces it, and closing steps
// back off a pushed entry, or replaces a card that came with the URL: see CLAUDE.md, "The map".
export const useSelection = (page, state, actions) => {
    const selected = selectedAirportIn(page.url);
    livePage = page;

    const select = useCallback((icao) => {
        const { url, props } = livePage;
        const was = selectedAirportIn(url);
        const next = icao || null;

        if (next === was) {
            return;
        }

        const visit = { url: withSelectedAirport(url, next), preserveState: true, preserveScroll: true };

        if (was === null) {
            if (!props?.[ORIGIN]) {
                const origin = newOrigin();
                router.replace({ props: (currentProps) => ({ ...currentProps, [ORIGIN]: origin }), preserveState: true, preserveScroll: true });
            }
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
