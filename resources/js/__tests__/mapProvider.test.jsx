// @vitest-environment jsdom
import { Component } from 'react';
import { act, render, screen } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { MAP_THEMES } from '../components/map/mapConfig';
import MapProvider from '../components/map/MapProvider';

const maplibre = vi.hoisted(() => ({ options: null, error: null }));

vi.mock('maplibre-gl/dist/maplibre-gl-worker.mjs?worker&url', () => ({ default: 'worker.js' }));
vi.mock('maplibre-gl', () => ({
    setWorkerUrl: vi.fn(),
    Map: vi.fn(function FakeMap(options) {
        if (maplibre.error) { throw maplibre.error; }
        maplibre.options = options;

        return { touchZoomRotate: { disableRotation: vi.fn() }, on: vi.fn(), remove: vi.fn() };
    }),
}));

class Boundary extends Component {
    state = { error: null };
    static getDerivedStateFromError(error) { return { error }; }
    render() { return this.state.error ? <p>fallback: {this.state.error.message}</p> : this.props.children; }
}

const view = { center: [0, 0], zoom: 2 };
const basemap = { version: 8, sources: {}, layers: [] };
let observed = null;
let size = 0;

beforeEach(() => {
    maplibre.options = null;
    maplibre.error = null;
    size = 800;
    vi.stubGlobal('ResizeObserver', class {
        constructor(callback) { observed = callback; }
        observe() {}
        disconnect() {}
    });
    vi.spyOn(HTMLElement.prototype, 'offsetWidth', 'get').mockImplementation(() => size);
    vi.spyOn(HTMLElement.prototype, 'offsetHeight', 'get').mockImplementation(() => size);
});

afterEach(() => {
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
});

describe('MapProvider', () => {
    it('themes every style load with the projection and theme current at that moment', () => {
        const { rerender } = render(<MapProvider view={view} projection="mercator" theme="default" />);
        const transform = maplibre.options.transformStyle;

        expect(transform(undefined, basemap)).toMatchObject({ projection: { type: 'mercator' }, sky: MAP_THEMES.default.sky });

        rerender(<MapProvider view={view} projection="globe" theme="light" />);

        expect(transform(undefined, basemap)).toMatchObject({ projection: { type: 'globe' }, sky: MAP_THEMES.light.sky });
    });

    it('hands a constructor failure from the resize observer to the error boundary', () => {
        vi.spyOn(console, 'error').mockImplementation(() => {});
        size = 0;
        maplibre.error = new Error('WebGL2 unavailable');

        render(<Boundary><MapProvider view={view} projection="mercator" theme="default" /></Boundary>);
        expect(screen.queryByText(/fallback/)).toBeNull();

        size = 800;
        act(() => observed());

        expect(screen.getByText('fallback: WebGL2 unavailable')).toBeTruthy();
    });
});
