// @vitest-environment jsdom
import { render } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

import { MapGLContext } from '../components/context/MapGLContext';
import MapFocusAirport from '../components/map/MapFocusAirport';

const fakeMap = () => {
    const sources = new Map();
    const layers = new Map();

    return {
        layers,
        sources,
        addSource: vi.fn((id, spec) => sources.set(id, { spec, setData: vi.fn() })),
        addLayer: vi.fn((layer) => layers.set(layer.id, layer)),
        removeLayer: vi.fn((id) => layers.delete(id)),
        removeSource: vi.fn((id) => sources.delete(id)),
        getLayer: (id) => layers.get(id),
        getSource: (id) => sources.get(id),
        getStyle: () => ({ layers: [] }),
    };
};

const renderMarker = (map, airport) => render(
    <MapGLContext.Provider value={map}>
        <MapFocusAirport airport={airport} color="#ddb81c" />
    </MapGLContext.Provider>,
);

describe('MapFocusAirport', () => {
    it('draws the focused airport on its own unclustered sources, on top', () => {
        const map = fakeMap();
        renderMarker(map, { icao: 'ENGM', lon: 11.1, lat: 60.2 });

        expect([...map.sources.keys()]).toEqual(['focus-airport', 'focus-airport-label']);
        expect([...map.sources.values()].every(({ spec }) => !spec.cluster)).toBe(true);
        expect(map.sources.get('focus-airport').spec.data.features[0].geometry.coordinates).toEqual([11.1, 60.2]);
        expect(map.layers.get('focus-airport-label').layout['text-field']).toEqual(['get', 'icao']);
        expect('filter' in map.layers.get('focus-airport-label')).toBe(false);
    });

    it('follows the focus and cleans up after itself', () => {
        const map = fakeMap();
        const { rerender, unmount } = renderMarker(map, { icao: 'ENGM', lon: 11.1, lat: 60.2 });

        rerender(
            <MapGLContext.Provider value={map}>
                <MapFocusAirport airport={{ icao: 'ESSA', lon: 17.9, lat: 59.6 }} color="#ddb81c" />
            </MapGLContext.Provider>,
        );
        expect(map.sources.get('focus-airport').setData).toHaveBeenLastCalledWith(
            expect.objectContaining({ features: [expect.objectContaining({ properties: expect.objectContaining({ icao: 'ESSA' }) })] }),
        );

        unmount();
        expect(map.sources.size).toBe(0);
        expect(map.layers.size).toBe(0);
    });
});
