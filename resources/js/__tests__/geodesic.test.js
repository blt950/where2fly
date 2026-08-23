import { describe, expect, it } from 'vitest';

import { arcDegrees, boundsFromCoordinates, greatCircle } from '../components/utils/geodesic';

const TOKYO = [139.78, 35.55];
const SFO = [-122.37, 37.62];

describe('arcDegrees', () => {
    it('is 0 for the same point', () => {
        expect(arcDegrees([10, 20], [10, 20])).toBe(0);
    });

    it('is 90 for a quarter turn along a meridian', () => {
        expect(arcDegrees([0, 0], [90, 0])).toBeCloseTo(90);
    });

    it('is 90 for a quarter turn along the equator', () => {
        expect(arcDegrees([0, 0], [0, 90])).toBeCloseTo(90);
    });

    it('is symmetric', () => {
        expect(arcDegrees(TOKYO, SFO)).toBeCloseTo(arcDegrees(SFO, TOKYO));
    });
});

describe('greatCircle', () => {
    it('starts and ends at the input points', () => {
        const line = greatCircle(TOKYO, SFO);

        expect(line[0][0]).toBeCloseTo(TOKYO[0]);
        expect(line[0][1]).toBeCloseTo(TOKYO[1]);
        expect(line.at(-1)[1]).toBeCloseTo(SFO[1]);
    });

    it('emits exactly segments + 1 points when given an explicit count', () => {
        expect(greatCircle([0, 0], [90, 0], 10)).toHaveLength(11);
    });

    it('passes through the midpoint of a quarter-turn arc', () => {
        const line = greatCircle([0, 0], [90, 0], 2);

        expect(line[1][0]).toBeCloseTo(45);
        expect(line[1][1]).toBeCloseTo(0);
    });

    it('keeps date-line longitudes continuous, never wrapping past 180', () => {
        const line = greatCircle(TOKYO, SFO);

        for (let i = 1; i < line.length; i++) {
            expect(Math.abs(line[i][0] - line[i - 1][0])).toBeLessThan(180);
        }
        expect(line.at(-1)[0]).toBeCloseTo(SFO[0] + 360);
    });

    it('falls back to a 2-point line with no NaN for identical points', () => {
        const line = greatCircle([12, 34], [12, 34]);

        expect(line).toEqual([[12, 34], [12, 34]]);
        line.flat().forEach((n) => expect(Number.isNaN(n)).toBe(false));
    });

    it('falls back to a 2-point line with no NaN for antipodal points', () => {
        const line = greatCircle([0, 0], [180, 0]);

        expect(line).toHaveLength(2);
        line.flat().forEach((n) => expect(Number.isNaN(n)).toBe(false));
    });

    it('produces no NaN/Infinity coordinates for the Tokyo-SFO line', () => {
        greatCircle(TOKYO, SFO).flat().forEach((n) => {
            expect(Number.isFinite(n)).toBe(true);
        });
    });
});

describe('boundsFromCoordinates', () => {
    it('boxes a simple cluster of points', () => {
        expect(boundsFromCoordinates([[0, 0], [10, 10], [5, 5]])).toEqual([[0, 0], [10, 10]]);
    });

    it('takes the short way round the date line instead of the naive 262deg box', () => {
        const bounds = boundsFromCoordinates([TOKYO, SFO]);
        const west = bounds[0][0];
        const east = bounds[1][0];

        expect(west).toBeCloseTo(139.78);
        expect(east).toBeCloseTo(237.63);
        expect(east - west).toBeLessThan(180);
    });
});
