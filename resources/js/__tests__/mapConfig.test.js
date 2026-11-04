import { describe, expect, it } from 'vitest';

import { GLYPHS_URL, MAP_THEMES, themedStyle } from '../components/map/mapConfig';

const basemap = () => ({
    version: 8,
    glyphs: 'https://tiles.basemaps.cartocdn.com/fonts/{fontstack}/{range}.pbf',
    sources: {},
    layers: [
        { id: 'background', type: 'background', paint: { 'background-color': '#0e0e0e' } },
        { id: 'water', type: 'fill', source: 'carto', paint: { 'fill-color': '#2c353c', 'fill-antialias': false } },
        { id: 'landcover', type: 'fill', source: 'carto', paint: { 'fill-color': '#111' } },
    ],
});

describe('themedStyle', () => {
    it('swaps in our glyphs, the chosen projection and the theme sky', () => {
        const style = themedStyle(basemap(), MAP_THEMES.default, 'globe');

        expect(style.glyphs).toBe(GLYPHS_URL);
        expect(style.projection).toEqual({ type: 'globe' });
        expect(style.sky).toBe(MAP_THEMES.default.sky);
    });

    it('merges overrides into matching layers and keeps their other paint properties', () => {
        const style = themedStyle(basemap(), MAP_THEMES.default, 'mercator');
        const water = style.layers.find((layer) => layer.id === 'water');

        expect(water.paint).toEqual({ 'fill-color': '#262626', 'fill-antialias': false });
        expect(style.layers.find((layer) => layer.id === 'landcover').paint).toEqual({ 'fill-color': '#111' });
    });

    it('never adds layers for overrides the basemap does not have', () => {
        const style = themedStyle(basemap(), MAP_THEMES.default, 'mercator');

        expect(style.layers.map((layer) => layer.id)).toEqual(['background', 'water', 'landcover']);
    });

    it('leaves the basemap layers alone for a theme without overrides', () => {
        const input = basemap();

        expect(themedStyle(input, MAP_THEMES.light, 'mercator').layers).toEqual(input.layers);
    });

    it('does not mutate the fetched style', () => {
        const input = basemap();
        themedStyle(input, MAP_THEMES.default, 'globe');

        expect(input).toEqual(basemap());
    });
});
