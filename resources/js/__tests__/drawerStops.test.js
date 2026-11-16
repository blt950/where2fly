import { describe, expect, it } from 'vitest';

import { clampOffset, settle, stepped, stopOffsets, tapped } from '../components/shell/drawerStops';

const offsets = stopOffsets(800, 64);

describe('stopOffsets', () => {
    it('derives every stop from the available height', () => {
        expect(offsets).toEqual({ full: 0, half: 400, peek: 736 });
        expect(stopOffsets(500, 64)).toEqual({ full: 0, half: 250, peek: 436 });
    });

    it('never puts the peek stop above the top', () => {
        expect(stopOffsets(40, 64).peek).toBe(0);
    });
});

describe('settle', () => {
    it('settles a slow drag on the nearest stop', () => {
        expect(settle(offsets, 150, 0.1)).toBe('full');
        expect(settle(offsets, 350, -0.2)).toBe('half');
        expect(settle(offsets, 650, 0)).toBe('peek');
    });

    it('moves a flick one stop in its direction, however little it travelled', () => {
        expect(settle(offsets, 380, -1)).toBe('full');
        expect(settle(offsets, 420, 1)).toBe('peek');
        expect(settle(offsets, 30, 1)).toBe('half');
        expect(settle(offsets, 700, -1)).toBe('half');
    });

    it('stays at the last stop when flicked past the end', () => {
        expect(settle(offsets, 0, -2)).toBe('full');
        expect(settle(offsets, 736, 2)).toBe('peek');
    });
});

describe('handle', () => {
    it('opens further on tap, dropping back to half from full', () => {
        expect(['peek', 'half', 'full'].map(tapped)).toEqual(['half', 'full', 'half']);
    });

    it('steps through every height from the keyboard', () => {
        expect(['peek', 'half', 'full'].map(stepped)).toEqual(['half', 'full', 'peek']);
    });

    it('keeps a drag inside the travel', () => {
        expect(clampOffset(offsets, -50)).toBe(0);
        expect(clampOffset(offsets, 900)).toBe(736);
    });
});
