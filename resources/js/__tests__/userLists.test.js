import { describe, expect, it } from 'vitest';

import { asList, mergeListAirports } from '../components/utils/userLists';

const list = (id, airports, hidden = false) => ({ id, name: `List ${id}`, color: '#fff', hidden, airports });

describe('asList', () => {
    it('passes arrays through untouched', () => {
        const lists = [list(1, { ENGM: {} })];

        expect(asList(lists)).toBe(lists);
    });

    // Every one of these reached lists.filter() before the guard and crashed the map
    it.each([
        ['null', null],
        ['undefined', undefined],
        ['an object keyed by id', { 1: list(1, {}) }],
        ['a double-encoded string', '[{"id":1}]'],
        ['a number', 7],
        ['a boolean', true],
    ])('falls back to an empty array for %s', (_label, value) => {
        expect(asList(value)).toEqual([]);
    });
});

describe('mergeListAirports', () => {
    it('merges the airports of every visible list', () => {
        const merged = mergeListAirports([
            list(1, { ENGM: { icao: 'ENGM' } }),
            list(2, { ENBR: { icao: 'ENBR' } }),
        ]);

        expect(Object.keys(merged).sort()).toEqual(['ENBR', 'ENGM']);
    });

    it('leaves out hidden lists', () => {
        const merged = mergeListAirports([
            list(1, { ENGM: { icao: 'ENGM' } }),
            list(2, { ENBR: { icao: 'ENBR' } }, true),
        ]);

        expect(Object.keys(merged)).toEqual(['ENGM']);
    });

    it('returns an empty object rather than throwing when lists is not an array', () => {
        expect(mergeListAirports({ 1: list(1, { ENGM: {} }) })).toEqual({});
        expect(mergeListAirports('[]')).toEqual({});
        expect(mergeListAirports(null)).toEqual({});
    });

    it('skips malformed entries instead of failing the whole overlay', () => {
        const merged = mergeListAirports([
            null,
            undefined,
            { id: 9 },
            list(1, 'not-an-object'),
            list(2, { ENGM: { icao: 'ENGM' } }),
        ]);

        expect(Object.keys(merged)).toEqual(['ENGM']);
    });
});
