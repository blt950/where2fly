// @vitest-environment jsdom
import { afterEach, describe, expect, it } from 'vitest';

import {
    POSITION_KEY,
    WORLD_VIEW,
    cameraView,
    initialMapState,
    mapStateReducer,
} from '../components/map/mapScene';

afterEach(() => {
    localStorage.clear();
});

describe('cameraView', () => {
    it('returns a fixed view as is', () => {
        expect(cameraView({ type: 'view', view: WORLD_VIEW })).toBe(WORLD_VIEW);
    });

    it('restores the saved position, zoom included', () => {
        localStorage.setItem(POSITION_KEY, JSON.stringify({ lng: 10, lat: 60, zoom: 6 }));

        expect(cameraView({ type: 'saved' })).toEqual({ center: [10, 60], zoom: 6 });
    });
});

describe('mapStateReducer', () => {
    it('replaces the whole scene and every transient value on a new scene', () => {
        const before = {
            ...initialMapState({ cluster: false, primaryAirport: 'ENGM', drawRoute: ['ENGM', 'ESSA'] }),
            focusAirport: 'ESSA', coordinates: [1, 2], ping: { icao: 'ESSA' }, showAirportIdCard: 7,
        };

        const after = mapStateReducer(before, { type: 'scene', scene: { airports: { EGLL: {} } } });

        expect(after).toMatchObject({
            airports: { EGLL: {} }, cluster: true, primaryAirport: null, drawRoute: null,
            focusAirport: null, coordinates: null, ping: null, showAirportIdCard: null,
        });
    });

    it('adds an airport without dropping the ones already there', () => {
        const state = initialMapState({ airports: { EGLL: { id: 1 } } });

        expect(mapStateReducer(state, { type: 'addAirport', icao: 'ESSA', airport: { id: 2 } }).airports)
            .toEqual({ EGLL: { id: 1 }, ESSA: { id: 2 } });
    });
});
