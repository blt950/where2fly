import { describe, expect, it } from 'vitest';

import { offsetFor, paddingFor } from '../components/context/MapLayoutContext';
import { fitPadding } from '../components/map/MapBound';

const mapOf = (clientWidth, clientHeight) => ({ getContainer: () => ({ clientWidth, clientHeight }) });

describe('camera insets', () => {
    it('pads each covered edge by what covers it plus a margin', () => {
        expect(paddingFor({ top: 0, right: 0, bottom: 300, left: 420 })).toEqual({ top: 50, right: 50, bottom: 350, left: 470 });
    });

    it('offsets a point to the middle of the uncovered area', () => {
        expect(offsetFor({ top: 0, right: 0, bottom: 400, left: 0 })).toEqual([0, -200]);
        expect(offsetFor({ top: 0, right: 0, bottom: 0, left: 420 })).toEqual([210, 0]);
    });

    it('frames within the uncovered area while it leaves room, else the whole map', () => {
        const drawer = { top: 0, right: 0, bottom: 365, left: 0 };

        expect(fitPadding(mapOf(390, 729), drawer)).toEqual(paddingFor(drawer));
        expect(fitPadding(mapOf(390, 500), drawer)).toEqual({ top: 50, right: 50, bottom: 50, left: 50 });
    });
});
