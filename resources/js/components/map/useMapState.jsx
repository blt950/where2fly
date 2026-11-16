import { useMemo, useReducer } from 'react';

import { initialMapState, mapStateReducer } from './mapScene';

// The airports can run to thousands and are rebuilt into a new scene on every card open or close:
// stringified once per object, so only a restored page (new objects, maybe the same content) pays
const airportSignatures = new WeakMap();

const signatureOf = ({ airports, ...rest }) => {
    if (airports === null || typeof airports !== 'object') {
        return JSON.stringify({ airports, ...rest });
    }

    if (!airportSignatures.has(airports)) {
        airportSignatures.set(airports, JSON.stringify(airports));
    }

    return `${JSON.stringify(rest)}|${airportSignatures.get(airports)}`;
};

// A scene whose content differs replaces the whole map state, so nothing leaks between pages; the
// same content (a page restored by Back) is left alone. Swapped during render, before child effects.
export const useMapState = (scene) => {
    const signature = useMemo(() => signatureOf(scene), [scene]);
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
