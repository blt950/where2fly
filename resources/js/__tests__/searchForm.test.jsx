// @vitest-environment jsdom
import { cleanup, render } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { formToOld, formUrl } from '../components/form/formQuery';

const sliderStarts = {};

vi.mock('@inertiajs/react', () => ({
    Head: () => null,
    Link: ({ children, ...props }) => <a {...props}>{children}</a>,
    router: { visit: vi.fn(), replace: vi.fn() },
    usePage: () => ({ props: { errors: {} } }),
}));
// Both own their DOM; what matters here is what the page hands them
vi.mock('../components/form/Combobox', () => ({ default: () => null }));
vi.mock('../components/form/RangeSlider', () => ({
    default: ({ id, start }) => { sliderStarts[id] = start; return null; },
}));

const { default: SearchForm } = await import('../pages/SearchForm');

const formProps = (old = {}) => ({
    anchor: 'departure', area: 'destination', suggestionSide: 'arrival', prefilledIcao: null, old,
    airlines: [], aircrafts: [], destinationInputs: {}, lists: [],
    aircraftTypes: { JM: { name: 'Narrow Body', description: 'B737' } },
    scoreTypes: { METAR_WINDY: { icon: 'fa-windsock', desc: 'Windy' }, VATSIM_ATC: { icon: 'fa-tower', desc: 'ATC' } },
    searchVersion: 2,
});

const checked = (id) => document.getElementById(id).checked;

beforeEach(() => {
    const router = Object.assign(() => 'search', { current: () => false });
    vi.stubGlobal('route', (name) => (name ? name : router));
    vi.stubGlobal('bootstrap', { Tooltip: { getOrCreateInstance: () => ({ dispose: vi.fn() }) } });
});

afterEach(() => {
    cleanup();
    vi.unstubAllGlobals();
});

describe('formQuery', () => {
    const form = (html) => {
        document.body.innerHTML = `<form action="http://w2f.test/search">${html}</form>`;
        return document.querySelector('form');
    };

    it('builds the query a native GET submit would, brackets and order kept', () => {
        const query = formUrl(form(`
            <input name="icao" value="ENGM">
            <select name="destinations[]" multiple><option value="C-EU" selected></option><option value="NO" selected></option></select>
            <input type="radio" name="scores[METAR_WINDY]" value="1" checked>
            <input type="checkbox" name="sortByATC" value="1">
        `));

        expect(decodeURIComponent(query)).toBe('http://w2f.test/search?icao=ENGM&destinations[]=C-EU&destinations[]=NO&scores[METAR_WINDY]=1');
    });

    it('shapes the fields like Laravel old input, recording named unchecked boxes as 0', () => {
        const old = formToOld(form(`
            <input name="icao" value="ENGM">
            <select name="destinations[]" multiple><option value="C-EU" selected></option></select>
            <input type="radio" name="scores[METAR_WINDY]" value="1" checked>
            <input type="checkbox" name="sortByWeather" value="1" checked>
            <input type="checkbox" name="sortByATC" value="1">
        `), ['sortByWeather', 'sortByATC']);

        expect(old).toEqual({ icao: 'ENGM', destinations: ['C-EU'], scores: { METAR_WINDY: '1' }, sortByWeather: '1', sortByATC: '0' });
    });
});

describe('SearchForm defaults', () => {
    it('starts from the documented defaults without old input', () => {
        render(<SearchForm {...formProps()} />);

        expect(checked('metcondition_all')).toBe(true);
        expect(checked('METAR_WINDY_neutral')).toBe(true);
        expect(checked('destinationAirbases_exclude')).toBe(true);
        expect(checked('flightDirection_neutral')).toBe(true);
        expect(checked('sortByWeather') && checked('sortByATC')).toBe(true);
        expect(checked('destinationAirportSizeSmall') && checked('destinationAirportSizeLarge')).toBe(true);
        expect(sliderStarts['slider-temperature']).toEqual([-60, 60]);
        expect(sliderStarts['slider-airtime']).toEqual([0, 5]);
    });

    it('fills every field back in from old input, zeros included', () => {
        render(<SearchForm {...formProps({
            metcondition: 'IFR', scores: { METAR_WINDY: '1' }, destinationAirbases: '0', flightDirection: 'NE',
            sortByWeather: '0', sortByATC: '1', destinationAirportSize: ['large_airport'],
            temperatureMin: '0', temperatureMax: '20', elevationMin: '0', elevationMax: '4000',
        })} />);

        expect(checked('metcondition_ifr')).toBe(true);
        expect(checked('METAR_WINDY_include')).toBe(true);
        // With old scores present, a reason missing from them is left unset, as before
        expect(['exclude', 'neutral', 'include'].some((state) => checked(`VATSIM_ATC_${state}`))).toBe(false);
        expect(checked('destinationAirbases_neutral')).toBe(true);
        expect(checked('flightDirection_northeast')).toBe(true);
        expect(checked('sortByWeather')).toBe(false);
        expect(checked('sortByATC')).toBe(true);
        expect(checked('destinationAirportSizeSmall')).toBe(false);
        expect(checked('destinationAirportSizeLarge')).toBe(true);
        expect(sliderStarts['slider-temperature']).toEqual([0, 20]);
        expect(sliderStarts['slider-elevation']).toEqual([0, 4000]);
    });

    it('prefers a prefilled ICAO and then locks the anchor whitelist', () => {
        render(<SearchForm {...formProps({ icao: 'EGLL' })} prefilledIcao="ENGM" />);

        expect(document.getElementById('icao').value).toBe('ENGM');
        expect(document.getElementById('anchorWhitelistLocked')).not.toBeNull();
    });
});
