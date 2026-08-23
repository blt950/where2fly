// @vitest-environment jsdom
import { afterEach, describe, expect, it, vi } from 'vitest';

import {
    AIRPORT_SOURCES,
    clusterIds,
    clusterSpecs,
    focusColor,
    hitId,
    labelIds,
    labelMinzoom,
    labelSpec,
    labelTypeFilter,
    NOT_A_CLUSTER,
} from '../components/utils/airportLayerSpec';

afterEach(() => {
    vi.unstubAllGlobals();
});

describe('layer id builders', () => {
    it('pins the full id set for a source prefix', () => {
        // A rename that misses a cross-module call site degrades into wrong z-ordering,
        // never an error, so the id set is pinned here.
        expect(hitId('airports')).toBe('airports-hit');
        expect(clusterIds('airports')).toEqual(['airports-clusters', 'airports-cluster-count']);
        expect(labelIds('airports')).toEqual([
            ['airports-label-large', 'large_airport'],
            ['airports-label-medium', 'medium_airport'],
            ['airports-label-small', 'small_airport'],
        ]);
    });

    it('names the two airport sources', () => {
        expect(AIRPORT_SOURCES).toEqual({ results: 'airports', userLists: 'user-list' });
    });
});

describe('focusColor', () => {
    it('selects the fallback palette color for the focused icao', () => {
        const palette = { fallback: '#ddb81c', candidate: '#808080' };

        expect(focusColor('ENGM', palette)).toEqual(['case',
            ['==', ['get', 'icao'], 'ENGM'], '#ddb81c', ['to-color', ['get', 'color']]]);
    });

    it('never matches when there is no focused airport', () => {
        const palette = { fallback: '#ddb81c', candidate: '#808080' };

        expect(focusColor(null, palette)).toEqual(['case',
            ['==', ['get', 'icao'], ''], '#ddb81c', ['to-color', ['get', 'color']]]);
        expect(focusColor(undefined, palette)).toEqual(['case',
            ['==', ['get', 'icao'], ''], '#ddb81c', ['to-color', ['get', 'color']]]);
    });
});

describe('labelTypeFilter', () => {
    it('matches large and medium airports by type equality', () => {
        expect(labelTypeFilter('large_airport')).toEqual(['==', ['get', 'type'], 'large_airport']);
        expect(labelTypeFilter('medium_airport')).toEqual(['==', ['get', 'type'], 'medium_airport']);
    });

    it('catches everything not large/medium for small_airport, so heliports and seaplane bases still get labelled', () => {
        expect(labelTypeFilter('small_airport')).toEqual(
            ['!', ['in', ['get', 'type'], ['literal', ['large_airport', 'medium_airport']]]],
        );
    });
});

describe('labelMinzoom', () => {
    it('gates by size when the route filters labels by zoom', () => {
        vi.stubGlobal('route', () => ({ current: (name) => name === 'search' }));

        expect(labelMinzoom('large_airport')).toBe(0);
        expect(labelMinzoom('medium_airport')).toBe(6);
        expect(labelMinzoom('small_airport')).toBe(8);
    });

    it('also gates when route().current() returns undefined', () => {
        vi.stubGlobal('route', () => ({ current: () => undefined }));

        expect(labelMinzoom('large_airport')).toBe(0);
        expect(labelMinzoom('medium_airport')).toBe(6);
        expect(labelMinzoom('small_airport')).toBe(8);
    });

    it('labels from zoom 0 for every size on other routes', () => {
        vi.stubGlobal('route', () => ({ current: (name) => name === 'top' }));

        expect(labelMinzoom('large_airport')).toBe(0);
        expect(labelMinzoom('medium_airport')).toBe(0);
        expect(labelMinzoom('small_airport')).toBe(0);
    });
});

describe('clusterSpecs', () => {
    const specs = clusterSpecs({ idPrefix: 'airports', source: 'airports', color: '#abc', textColor: '#def' });

    it('returns exactly 2 layers whose ids match clusterIds', () => {
        expect(specs).toHaveLength(2);
        expect(specs.map((spec) => spec.id)).toEqual(clusterIds('airports'));
    });

    it('gives the circle layer the cluster color and the pinned step thresholds/radii', () => {
        const [circle] = specs;

        expect(circle.paint['circle-color']).toBe('#abc');
        // Pinned explicitly: a cluster-size retune should show up as a failing test.
        expect(circle.paint['circle-radius']).toEqual(['step', ['get', 'point_count'], 20, 100, 30, 750, 40]);
        expect(circle.filter).toEqual(['has', 'point_count']);
    });

    it('gives the symbol layer the text color and the abbreviated count field', () => {
        const [, symbol] = specs;

        expect(symbol.paint['text-color']).toBe('#def');
        expect(symbol.layout['text-field']).toBe('{point_count_abbreviated}');
        expect(symbol.filter).toEqual(['has', 'point_count']);
    });
});

describe('labelSpec', () => {
    const base = { id: 'foo', source: 'airports', filter: NOT_A_CLUSTER };

    it('defaults to minzoom 0 with no overlap keys', () => {
        const spec = labelSpec(base);

        expect(spec.minzoom).toBe(0);
        expect(spec.layout).not.toHaveProperty('text-allow-overlap');
        expect(spec.layout).not.toHaveProperty('text-ignore-placement');
    });

    it('adds overlap keys when overlap is true', () => {
        const spec = labelSpec({ ...base, overlap: true });

        expect(spec.layout['text-allow-overlap']).toBe(true);
        expect(spec.layout['text-ignore-placement']).toBe(true);
    });

    it('colors and sizes text from the per-feature expression', () => {
        const spec = labelSpec(base);

        expect(spec.paint['text-color']).toEqual(['to-color', ['get', 'color']]);
        expect(spec.layout['text-size']).toBe(parseFloat(getComputedStyle(document.documentElement).fontSize) || 16);
    });
});

describe('NOT_A_CLUSTER', () => {
    it('is the negated point_count check', () => {
        expect(NOT_A_CLUSTER).toEqual(['!', ['has', 'point_count']]);
    });
});
