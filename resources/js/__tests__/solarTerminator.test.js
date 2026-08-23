import { describe, expect, it } from 'vitest';

import { terminatorPolygon } from '../components/utils/solarTerminator';

const meanLat = (points) => points.reduce((sum, [, lat]) => sum + lat, 0) / points.length;

describe('terminatorPolygon', () => {
    it('closes the ring: first coordinate equals last', () => {
        const [ring] = terminatorPolygon(new Date('2026-01-01T00:00:00Z')).geometry.coordinates;

        expect(ring[0]).toEqual(ring.at(-1));
    });

    it('emits 724 points: 1 pole start + 721 sweep + 2 closing pole points', () => {
        const [ring] = terminatorPolygon(new Date('2026-01-01T00:00:00Z')).geometry.coordinates;

        expect(ring).toHaveLength(724);
    });

    it('caps the north pole in northern winter, when the sun sits south', () => {
        const [ring] = terminatorPolygon(new Date('2026-01-01T00:00:00Z')).geometry.coordinates;

        expect(ring[0]).toEqual([-180, 90]);
        expect(meanLat(ring.slice(1, 722))).toBeGreaterThan(0);
    });

    it('caps the south pole in northern summer, when the sun sits north', () => {
        const [ring] = terminatorPolygon(new Date('2026-07-01T00:00:00Z')).geometry.coordinates;

        expect(ring[0]).toEqual([-180, -90]);
        expect(meanLat(ring.slice(1, 722))).toBeLessThan(0);
    });

    it('keeps every latitude within [-90, 90] and finite', () => {
        const [ring] = terminatorPolygon(new Date('2026-01-01T00:00:00Z')).geometry.coordinates;

        ring.forEach(([lon, lat]) => {
            expect(Number.isFinite(lon)).toBe(true);
            expect(Number.isFinite(lat)).toBe(true);
            expect(lat).toBeGreaterThanOrEqual(-90);
            expect(lat).toBeLessThanOrEqual(90);
        });
    });
});
