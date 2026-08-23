// @vitest-environment jsdom
import { renderHook } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

import { MapGLContext } from '../components/context/MapGLContext';
import { beneath, themeOf } from '../components/map/mapConfig';
import { removeSourceLayer, useMapLayer } from '../components/map/mapLayers';

// vi.fn() so call args/order are assertable; Sets stand in for the real style's layer/source state.
const createFakeMap = ({ layers = [] } = {}) => {
    const layerIds = new Set(layers);
    const sourceIds = new Set();
    const order = [];

    const map = {
        addSource: vi.fn((id) => { sourceIds.add(id); order.push(['addSource', id]); }),
        addLayer: vi.fn((layer) => { layerIds.add(layer.id); order.push(['addLayer', layer.id]); }),
        removeLayer: vi.fn((id) => { layerIds.delete(id); order.push(['removeLayer', id]); }),
        removeSource: vi.fn((id) => { sourceIds.delete(id); order.push(['removeSource', id]); }),
        getLayer: (id) => (layerIds.has(id) ? {} : undefined),
        getSource: (id) => (sourceIds.has(id) ? {} : undefined),
    };

    return { map, order };
};

const source = { type: 'geojson', data: { type: 'FeatureCollection', features: [] } };
const layer = { type: 'circle', paint: {} };
// A named function, not an inline arrow, so react/display-name doesn't flag it.
function wrapperFor(map) {
    return function MapGLWrapper({ children }) {
        return <MapGLContext.Provider value={map}>{children}</MapGLContext.Provider>;
    };
}

describe('removeSourceLayer', () => {
    it('removes both layer and source when both exist', () => {
        const { map } = createFakeMap({ layers: ['x'] });
        map.addSource('x', source);

        removeSourceLayer(map, 'x');

        expect(map.removeLayer).toHaveBeenCalledWith('x');
        expect(map.removeSource).toHaveBeenCalledWith('x');
    });

    it('removes just the source, without calling removeLayer, when only the source exists', () => {
        const { map } = createFakeMap();
        map.addSource('x', source);

        removeSourceLayer(map, 'x');

        expect(map.removeLayer).not.toHaveBeenCalled();
        expect(map.removeSource).toHaveBeenCalledWith('x');
    });

    it('does not throw when neither layer nor source exists', () => {
        const { map } = createFakeMap();

        expect(() => removeSourceLayer(map, 'x')).not.toThrow();
        expect(map.removeLayer).not.toHaveBeenCalled();
        expect(map.removeSource).not.toHaveBeenCalled();
    });
});

describe('useMapLayer', () => {
    it('adds the source then the layer on mount, resolving `below` to the first existing id', () => {
        const { map } = createFakeMap({ layers: ['existingLayer'] });

        renderHook(() => useMapLayer({ id: 'foo', source, layer, below: ['missingLayer', 'existingLayer'] }), {
            wrapper: wrapperFor(map),
        });

        expect(map.addSource).toHaveBeenCalledWith('foo', source);
        expect(map.addLayer).toHaveBeenCalledWith({ id: 'foo', source: 'foo', ...layer }, 'existingLayer');
    });

    it('removes the layer before the source on unmount', () => {
        const { map, order } = createFakeMap();

        const { unmount } = renderHook(() => useMapLayer({ id: 'foo', source, layer }), { wrapper: wrapperFor(map) });
        unmount();

        const teardown = order.filter(([action]) => action === 'removeLayer' || action === 'removeSource');
        expect(teardown).toEqual([['removeLayer', 'foo'], ['removeSource', 'foo']]);
    });

    it('tears down and re-adds the source/layer when a dep changes', () => {
        const { map } = createFakeMap();

        const { rerender } = renderHook(({ dep }) => useMapLayer({ id: 'foo', source, layer }, [dep]), {
            wrapper: wrapperFor(map),
            initialProps: { dep: 1 },
        });

        expect(map.addSource).toHaveBeenCalledTimes(1);

        rerender({ dep: 2 });

        expect(map.removeLayer).toHaveBeenCalledWith('foo');
        expect(map.removeSource).toHaveBeenCalledWith('foo');
        expect(map.addSource).toHaveBeenCalledTimes(2);
        expect(map.addLayer).toHaveBeenCalledTimes(2);
    });
});

describe('beneath', () => {
    it('returns the first id that exists on the map', () => {
        const { map } = createFakeMap({ layers: ['b'] });

        expect(beneath(map, ['a', 'b', 'c'])).toBe('b');
    });

    it('returns undefined when none of the ids exist', () => {
        const { map } = createFakeMap();

        expect(beneath(map, ['a', 'b'])).toBeUndefined();
    });
});

describe('themeOf', () => {
    it('falls back to the default theme for an unknown key', () => {
        expect(themeOf('does-not-exist')).toBe(themeOf('default'));
    });
});
