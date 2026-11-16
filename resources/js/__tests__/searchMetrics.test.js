// @vitest-environment jsdom
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { trackSearch } from '../components/form/searchMetrics';

const form = (html) => {
    document.body.innerHTML = `<form>${html}</form>`;
    return document.querySelector('form');
};

const FIELDS = `
    <input name="icao" value="">
    <select name="codeletter"><option value="JM" selected></option></select>
    <input type="checkbox" name="sortByWeather" checked>
    <input type="checkbox" name="sortByATC">
    <select name="destinations[]" multiple><option value="C-EU" selected></option><option value="NO" selected></option></select>
    <select name="airlines[]" multiple><option value="SAS"></option></select>
    <select name="departureWhitelists[]" multiple disabled><option value="3" selected></option></select>
    <input type="radio" name="scores[METAR_WINDY]" value="1" checked>
    <input type="radio" name="scores[METAR_WINDY]" value="-1">
    <input type="radio" name="scores[VATSIM_ATC]" value="0">
    <input type="radio" name="metcondition" value="IFR" checked>
    <input name="airtimeMin" value="1"><input name="airtimeMax" value="5">
    <input name="distanceMin" value="0"><input name="distanceMax" value="6000">
`;

beforeEach(() => { window.umami = { track: vi.fn() }; });
afterEach(() => { delete window.umami; });

describe('trackSearch', () => {
    it('reports which filters a search used', () => {
        trackSearch(form(FIELDS));

        expect(window.umami.track).toHaveBeenCalledWith('Search', { filter: expect.objectContaining({
            icao: 'Random',
            codeletter: 'JM',
            sortByWeather: true,
            sortByATC: false,
            METAR_WINDY: '1',
            metcondition: 'IFR',
            hours: '1-5',
            distance: '0-6000',
            destinations: ['C-EU', 'NO'],
        }) });
    });

    it('leaves out a score nobody chose and a slider the form does not have', () => {
        trackSearch(form(FIELDS));
        const { filter } = window.umami.track.mock.calls[0][1];

        expect(filter).not.toHaveProperty('VATSIM_ATC');
        expect(filter).not.toHaveProperty('temperature');
    });

    it('names the default for an empty or disabled multiselect', () => {
        trackSearch(form(FIELDS));
        const { filter } = window.umami.track.mock.calls[0][1];

        expect(filter.airlines).toEqual(['Any']);
        // Disabled by a typed ICAO: its selection doesn't count
        expect(filter.departureWhitelists).toEqual(['None']);
        expect(filter.aircrafts).toEqual(['Any']);
    });

    it('reports the typed ICAO, and does nothing without umami', () => {
        trackSearch(form('<input name="icao" value="ENGM">'));
        expect(window.umami.track.mock.calls[0][1].filter.icao).toBe('ENGM');

        delete window.umami;
        expect(() => trackSearch(form(FIELDS))).not.toThrow();
    });
});
