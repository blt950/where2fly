// @vitest-environment jsdom
import { afterEach, describe, expect, it } from 'vitest';

import {
    CONTINENT_VIEWS,
    POSITION_KEY,
    WORLD_VIEW,
    cameraView,
    initialMapState,
    mapStateReducer,
    sceneFromRoute,
} from '../components/map/mapScene';

// Ziggy's current(): no argument returns the name, a pattern with a trailing * matches prefixes,
// and a params object must match the route's params.
const routerAt = (name, params = {}) => ({
    current: (pattern, wanted) => {
        if (pattern === undefined) {
            return name;
        }

        if (name === undefined) {
            return false;
        }

        const matches = pattern.endsWith('*') ? name.startsWith(pattern.slice(0, -1)) : name === pattern;

        return matches && (wanted === undefined || Object.values(params).includes(wanted));
    },
});

afterEach(() => {
    localStorage.clear();
});

describe('sceneFromRoute', () => {
    it('gives the default view on the front page', () => {
        const scene = sceneFromRoute(routerAt('front'));

        expect(scene).toMatchObject({
            camera: { type: 'saved' }, clusterTone: 'muted', persistView: true, showLists: true, gateLabels: false,
        });
    });

    it('frames gated, gold results on search', () => {
        expect(sceneFromRoute(routerAt('search'))).toMatchObject({
            camera: { type: 'fit' }, clusterTone: 'search', persistView: false, showLists: false, gateLabels: true,
        });
    });

    it('frames route results without gating labels', () => {
        expect(sceneFromRoute(routerAt('search.routes'))).toMatchObject({
            camera: { type: 'fit' }, gateLabels: false, persistView: false,
        });
    });

    it('uses the world view on top and the continent view on top.filtered', () => {
        expect(sceneFromRoute(routerAt('top')).camera).toEqual({ type: 'view', view: WORLD_VIEW });
        expect(sceneFromRoute(routerAt('top.filtered', { continent: 'EU' })).camera)
            .toEqual({ type: 'view', view: CONTINENT_VIEWS.EU });
    });

    it('persists the view on scenery without fitting or showing lists', () => {
        expect(sceneFromRoute(routerAt('scenery.filtered', { simulator: 'msfs' }))).toMatchObject({
            camera: { type: 'saved' }, clusterTone: 'search', persistView: true, showLists: false,
        });
    });

    it('treats an unnamed route as results that fit and gate labels', () => {
        expect(sceneFromRoute(routerAt(undefined))).toMatchObject({
            camera: { type: 'fit' }, gateLabels: true, showLists: false, persistView: false,
        });
    });
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
            focusAirport: 'ESSA', coordinates: [1, 2], mapBounds: [[1, 2]], ping: { icao: 'ESSA' }, showAirportIdCard: 7,
        };

        const after = mapStateReducer(before, { type: 'scene', scene: { airports: { EGLL: {} } } });

        expect(after).toMatchObject({
            airports: { EGLL: {} }, cluster: true, primaryAirport: null, drawRoute: null,
            focusAirport: null, coordinates: null, mapBounds: null, ping: null, showAirportIdCard: null,
        });
    });

    it('adds an airport without dropping the ones already there', () => {
        const state = initialMapState({ airports: { EGLL: { id: 1 } } });

        expect(mapStateReducer(state, { type: 'addAirport', icao: 'ESSA', airport: { id: 2 } }).airports)
            .toEqual({ EGLL: { id: 1 }, ESSA: { id: 2 } });
    });
});
