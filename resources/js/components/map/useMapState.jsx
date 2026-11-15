import { useMemo, useReducer } from 'react';

import { initialMapState, mapStateReducer } from './mapScene';

// One map state per mount. A new sceneKey (a new page visit) replaces the whole scene, so
// nothing from the previous page (focus, route, card) leaks into the next. It's swapped during
// render, not in an effect: child effects run first and would see the new key with the old scene.
export const useMapState = (scene, sceneKey) => {
    const [state, dispatch] = useReducer(mapStateReducer, { scene, sceneKey },
        (init) => ({ ...initialMapState(init.scene), sceneKey: init.sceneKey }));

    if (state.sceneKey !== sceneKey) {
        dispatch({ type: 'scene', scene, sceneKey });
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
