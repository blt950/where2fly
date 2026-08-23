import { describe, expect, it } from 'vitest';

import { airportsToGeoJson } from '../components/utils/airportsGeoJson';

const palette = { fallback: '#f00', candidate: '#888' };

describe('airportsToGeoJson', () => {
    it('maps a missing color to the fallback palette color', () => {
        const geojson = airportsToGeoJson({ a: { id: 1, icao: 'AAAA', lon: 1, lat: 2 } }, palette);

        expect(geojson.features[0].properties.color).toBe(palette.fallback);
    });

    it('maps the literal "grey" candidate marker to the candidate palette color', () => {
        const geojson = airportsToGeoJson({ a: { id: 1, icao: 'AAAA', lon: 1, lat: 2, color: 'grey' } }, palette);

        expect(geojson.features[0].properties.color).toBe(palette.candidate);
    });

    it('passes any other color value through untouched', () => {
        const geojson = airportsToGeoJson({ a: { id: 1, icao: 'AAAA', lon: 1, lat: 2, color: '#00ff00' } }, palette);

        expect(geojson.features[0].properties.color).toBe('#00ff00');
    });

    it.each([
        ['large_airport', 5],
        ['medium_airport', 3.5],
        ['small_airport', 2.5],
        ['heliport', 2.5],
        [undefined, 2.5],
    ])('gives type %s a radius of %s', (type, radius) => {
        const geojson = airportsToGeoJson({ a: { id: 1, icao: 'AAAA', lon: 1, lat: 2, type } }, palette);

        expect(geojson.features[0].properties.r).toBe(radius);
    });

    it('defaults an unknown/missing type to small_airport', () => {
        const geojson = airportsToGeoJson({ a: { id: 1, icao: 'AAAA', lon: 1, lat: 2 } }, palette);

        expect(geojson.features[0].properties.type).toBe('small_airport');
    });

    it('converts string lon/lat inputs to numbers', () => {
        const geojson = airportsToGeoJson({ a: { id: 1, icao: 'AAAA', lon: '10.5', lat: '-20.25' } }, palette);

        expect(geojson.features[0].geometry.coordinates).toEqual([10.5, -20.25]);
    });

    it.each([null, undefined])('returns an empty FeatureCollection for %s airports', (airports) => {
        const geojson = airportsToGeoJson(airports, palette);

        expect(geojson).toEqual({ type: 'FeatureCollection', features: [] });
    });
});
