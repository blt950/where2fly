import { useMemo, useReducer } from 'react';

import { initialMapState, mapStateReducer } from './mapScene';

// The map's state for whatever scene the page describes. A scene that actually differs replaces
// everything (focus, route, card), so nothing leaks between pages; one with the same content is
// left alone. That covers Back after opening a card (a restored page, same scene), and Randomise
// (same URL, new results). Swapped during render: child effects would otherwise run first, with
// the new scene's key but the old scene's state.
export const useMapState = (scene) => {
    const signature = useMemo(() => JSON.stringify(scene), [scene]);
    const [state, dispatch] = useReducer(mapStateReducer, { scene, signature },
        (init) => ({ ...initialMapState(init.scene), signature: init.signature, sceneVersion: 1 }));

    if (state.signature !== signature) {
        dispatch({ type: 'scene', scene, signature });
    }

    const actions = useMemo(() => ({
        set: (values) => dispatch({ type: 'set', values }),
        addAirport: (icao, airport) => dispatch({ type: 'addAirport', icao, airport }),
        setFocusAirport: (icao) => dispatch({ type: 'set', values: { focusAirport: icao } }),
        setShowAirportIdCard: (id) => dispatch({ type: 'set', values: { showAirportIdCard: id } }),
        pingAirport: (icao) => dispatch({ type: 'set', values: { ping: { icao, ts: Date.now() } } }),
    }), []);

    return [state, actions];
};
