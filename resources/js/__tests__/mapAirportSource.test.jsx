// @vitest-environment jsdom
import { render } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { MapContext } from '../components/context/MapContext';
import { MapGLContext } from '../components/context/MapGLContext';
import { clusterIds, hitId, labelIds } from '../components/utils/airportLayerSpec';
import MapAirportSource from '../components/map/MapAirportSource';

// Sets back getLayer/getSource; on/off record handlers keyed by event so tests can invoke them.
const createFakeMap = () => {
    const layerIds = new Set();
    const sourceIds = new Set();
    const handlers = {};
    const sources = new Map();

    const map = {
        addSource: vi.fn((id) => {
            sourceIds.add(id);
            sources.set(id, { setData: vi.fn(), getClusterExpansionZoom: vi.fn().mockResolvedValue(7) });
        }),
        addLayer: vi.fn((layer) => layerIds.add(layer.id)),
        removeLayer: vi.fn((id) => layerIds.delete(id)),
        removeSource: vi.fn((id) => { sourceIds.delete(id); sources.delete(id); }),
        getLayer: (id) => (layerIds.has(id) ? {} : undefined),
        getSource: (id) => sources.get(id),
        setPaintProperty: vi.fn(),
        setFilter: vi.fn(),
        easeTo: vi.fn(),
        getCanvas: vi.fn(() => ({ style: {} })),
        on: vi.fn((event, layers, handler) => { handlers[event] = handler; }),
        off: vi.fn((event) => { delete handlers[event]; }),
    };

    return { map, handlers, layerIds };
};

const wrapperFor = (map, mapContext) => function Wrapper({ children }) {
    return (
        <MapGLContext.Provider value={map}>
            <MapContext.Provider value={mapContext}>{children}</MapContext.Provider>
        </MapGLContext.Provider>
    );
};

const baseProps = { id: 'airports', airports: {}, palette: { fallback: '#ddb81c', candidate: '#808080' }, cluster: true, clusterColor: '#abc', clusterTextColor: '#def' };

beforeEach(() => {
    // labelMinzoom reads the Ziggy route() global; not exercised by these tests, so any route works.
    vi.stubGlobal('route', () => ({ current: () => undefined }));
});

afterEach(() => {
    vi.unstubAllGlobals();
});

describe('MapAirportSource clicks', () => {
    it('focuses the airport when a non-cluster feature is clicked', () => {
        const { map, handlers } = createFakeMap();
        const setFocusAirport = vi.fn();

        render(<MapAirportSource {...baseProps} />, {
            wrapper: wrapperFor(map, { focusAirport: null, primaryAirport: null, setFocusAirport }),
        });

        handlers.click({ features: [{ properties: { icao: 'ENGM' }, geometry: { coordinates: [11, 60] } }] });

        expect(setFocusAirport).toHaveBeenCalledWith('ENGM');
        expect(map.easeTo).not.toHaveBeenCalled();
    });

    it('expands a cluster instead of focusing', async () => {
        const { map, handlers } = createFakeMap();
        const setFocusAirport = vi.fn();

        render(<MapAirportSource {...baseProps} />, {
            wrapper: wrapperFor(map, { focusAirport: null, primaryAirport: null, setFocusAirport }),
        });

        const source = map.getSource('airports');
        await handlers.click({ features: [{ properties: { cluster_id: 3, point_count: 12 }, geometry: { coordinates: [11, 60] } }] });

        expect(source.getClusterExpansionZoom).toHaveBeenCalledWith(3);
        expect(map.easeTo).toHaveBeenCalledWith({ center: [11, 60], zoom: 7 });
        expect(setFocusAirport).not.toHaveBeenCalled();
    });
});

describe('MapAirportSource mount wiring', () => {
    it('adds the geojson source with clustering options', () => {
        const { map } = createFakeMap();

        render(<MapAirportSource {...baseProps} />, {
            wrapper: wrapperFor(map, { focusAirport: null, primaryAirport: null, setFocusAirport: vi.fn() }),
        });

        expect(map.addSource).toHaveBeenCalledWith('airports', expect.objectContaining({ cluster: true, clusterMaxZoom: 12 }));
    });

    it('adds the cluster layers when cluster is true', () => {
        const { map } = createFakeMap();

        render(<MapAirportSource {...baseProps} cluster />, {
            wrapper: wrapperFor(map, { focusAirport: null, primaryAirport: null, setFocusAirport: vi.fn() }),
        });

        const addedIds = map.addLayer.mock.calls.map(([layer]) => layer.id);
        clusterIds('airports').forEach((id) => expect(addedIds).toContain(id));
    });

    it('skips the cluster layers when cluster is false', () => {
        const { map } = createFakeMap();

        render(<MapAirportSource {...baseProps} cluster={false} />, {
            wrapper: wrapperFor(map, { focusAirport: null, primaryAirport: null, setFocusAirport: vi.fn() }),
        });

        const addedIds = map.addLayer.mock.calls.map(([layer]) => layer.id);
        clusterIds('airports').forEach((id) => expect(addedIds).not.toContain(id));
    });

    it('adds the invisible hit layer, which is what makes a 5px small_airport clickable', () => {
        const { map } = createFakeMap();

        render(<MapAirportSource {...baseProps} />, {
            wrapper: wrapperFor(map, { focusAirport: null, primaryAirport: null, setFocusAirport: vi.fn() }),
        });

        const hit = map.addLayer.mock.calls.map(([layer]) => layer).find((layer) => layer.id === hitId('airports'));
        expect(hit.paint).toEqual({ 'circle-radius': 9, 'circle-opacity': 0 });
    });
});

describe('MapAirportSource teardown', () => {
    it('removes every layer it added, then the source', () => {
        const { map } = createFakeMap();

        const { unmount } = render(<MapAirportSource {...baseProps} />, {
            wrapper: wrapperFor(map, { focusAirport: null, primaryAirport: null, setFocusAirport: vi.fn() }),
        });

        const addedIds = new Set(map.addLayer.mock.calls.map(([layer]) => layer.id));
        unmount();

        const removedIds = new Set(map.removeLayer.mock.calls.map(([id]) => id));
        expect(removedIds).toEqual(addedIds);
        expect(map.removeSource).toHaveBeenCalledWith('airports');
    });
});

describe('MapAirportSource data updates', () => {
    it('calls setData on prop change instead of re-adding the source', () => {
        const { map } = createFakeMap();

        const { rerender } = render(<MapAirportSource {...baseProps} />, {
            wrapper: wrapperFor(map, { focusAirport: null, primaryAirport: null, setFocusAirport: vi.fn() }),
        });

        expect(map.addSource).toHaveBeenCalledTimes(1);

        const airports = { 1: { id: 1, icao: 'ENGM', lat: 60, lon: 11, type: 'large_airport' } };
        rerender(<MapAirportSource {...baseProps} airports={airports} />);

        expect(map.addSource).toHaveBeenCalledTimes(1);
        expect(map.getSource('airports').setData).toHaveBeenCalledWith(
            expect.objectContaining({ features: expect.arrayContaining([expect.objectContaining({ properties: expect.objectContaining({ icao: 'ENGM' }) })]) }),
        );
    });
});

describe('MapAirportSource focus repaint', () => {
    it('repaints dots/labels and refilters the pinned layer when focusAirport changes', () => {
        const { map } = createFakeMap();
        // Not using render's `wrapper` option here: it would re-wrap every rerender in the
        // FIRST call's closure, masking the new context value with the stale one.
        const tree = (mapContext) => (
            <MapGLContext.Provider value={map}>
                <MapContext.Provider value={mapContext}>
                    <MapAirportSource {...baseProps} />
                </MapContext.Provider>
            </MapGLContext.Provider>
        );

        const { rerender } = render(tree({ focusAirport: null, primaryAirport: 'ESSA', setFocusAirport: vi.fn() }));

        map.setPaintProperty.mockClear();
        map.setFilter.mockClear();

        rerender(tree({ focusAirport: 'ENGM', primaryAirport: 'ESSA', setFocusAirport: vi.fn() }));

        const dotsCall = map.setPaintProperty.mock.calls.find(([id, prop]) => id === 'airports-dots' && prop === 'circle-color');
        expect(dotsCall).toBeTruthy();

        const labelCalls = map.setPaintProperty.mock.calls.filter(([id, prop]) => labelIds('airports').some(([labelId]) => labelId === id) && prop === 'text-color');
        expect(labelCalls.length).toBeGreaterThan(0);

        expect(map.setFilter).toHaveBeenCalledWith('airports-label-pinned', ['all', ['!', ['has', 'point_count']], ['in', ['get', 'icao'], ['literal', ['ENGM', 'ESSA']]]]);
    });
});
