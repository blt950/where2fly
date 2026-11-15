// @vitest-environment jsdom
import { cleanup, fireEvent, render, screen, within } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { ShellMapContext } from '../components/shell/ShellMapContext';

const routerVisit = vi.fn();

vi.mock('@inertiajs/react', () => ({
    Head: () => null,
    router: { visit: (...args) => routerVisit(...args) },
}));

const { default: SearchResults } = await import('../pages/SearchResults');
const { default: SearchRoutes } = await import('../pages/SearchRoutes');

const airport = (icao, rank, extra = {}) => ({
    icao, rank, name: `${icao} Airport`, isoCountry: 'NO', countryName: 'Norway',
    scoreCount: 0, distance: 100 * rank, airtime: rank, airtimeLabel: `${rank}:00`,
    scores: [], runways: [{ id: rank, ident: '01/19', lengthFt: 10000 }],
    metar: `${icao} METAR`, taf: null, sceneries: [], departureIcao: 'ENGM', arrivalIcao: icao,
    ...extra,
});

const resultsProps = (count) => ({
    query: { icao: 'ENGM', direction: 'departure', scores: { METAR_WINDY: '1' } },
    direction: 'departure',
    suggestedAirport: false,
    bearingWarning: null,
    searchVersionWarning: null,
    noInterestingScores: false,
    primaryAirport: { ...airport('ENGM', 0), longestRunwayFt: 11811, taf: 'ENGM TAF TEXT' },
    suggestions: Array.from({ length: count }, (_, i) => airport(`EA${String(i + 1).padStart(2, '0')}`, i + 1)),
    scoreTypes: {},
});

const renderWithShell = (ui, shell = {}) => {
    const value = { focusAirport: null, setFocusAirport: vi.fn(), pingAirport: vi.fn(), airportNotFound: null, ...shell };

    return { ...render(<ShellMapContext.Provider value={value}>{ui}</ShellMapContext.Provider>), shell: value };
};

const flightOf = (row) => row.querySelector('.dropdown-toggle').textContent;
const rowIcaos = () => [...document.querySelectorAll('tr[data-airport-icao]')].map((row) => row.dataset.airportIcao);
const hiddenRows = () => document.querySelectorAll('tr.showmore-hidden').length;

beforeEach(() => {
    document.head.innerHTML = '<meta name="csrf-token" content="test-token">';
    vi.stubGlobal('route', (name, params) => (params ? `${name}?${new URLSearchParams(params)}` : name));
    vi.stubGlobal('bootstrap', { Tooltip: { getOrCreateInstance: () => ({ dispose: vi.fn() }) } });
});

afterEach(() => {
    cleanup();
    vi.unstubAllGlobals();
    routerVisit.mockClear();
});

describe('SearchResults', () => {
    it('folds rows past the first ten behind "Show more"', () => {
        renderWithShell(<SearchResults {...resultsProps(14)} />);

        expect(hiddenRows()).toBe(4);
        fireEvent.click(screen.getByText('Show more'));

        expect(hiddenRows()).toBe(0);
        expect(screen.queryByText('Show more')).toBeNull();
    });

    it('unfolds every row when a column is sorted', () => {
        renderWithShell(<SearchResults {...resultsProps(14)} />);

        fireEvent.click(screen.getByText('Distance'));
        fireEvent.click(screen.getByText('Distance'));

        expect(hiddenRows()).toBe(0);
        expect(rowIcaos()[0]).toBe('EA14');
    });

    it('focuses the airport and toggles its detail row on click', () => {
        const { shell } = renderWithShell(<SearchResults {...resultsProps(3)} />);
        const row = document.querySelector('tr[data-airport-icao="EA02"]');

        fireEvent.click(row);
        expect(shell.setFocusAirport).toHaveBeenCalledWith('EA02');
        expect(document.querySelector('tr[data-detail-row="EA02"]')).not.toBeNull();

        fireEvent.click(row);
        expect(document.querySelector('tr[data-detail-row="EA02"]')).toBeNull();
    });

    it('unfolds the rows when the map focuses an airport behind "Show more"', () => {
        renderWithShell(<SearchResults {...resultsProps(14)} />, { focusAirport: 'EA13' });

        expect(hiddenRows()).toBe(0);
        expect(document.querySelector('tr.active').dataset.airportIcao).toBe('EA13');
    });

    it('posts the query back to the form for editing, nested fields included', () => {
        renderWithShell(<SearchResults {...resultsProps(1)} />);
        const fields = Object.fromEntries([...document.querySelectorAll('#filtersForm input[type=hidden]')].map((input) => [input.name, input.value]));

        expect(fields).toMatchObject({
            _token: 'test-token', icao: 'ENGM', direction: 'departure', 'scores[METAR_WINDY]': '1', sortByWeather: '0', sortByATC: '0',
        });
    });

    it('randomises with a fresh visit to the same search', () => {
        renderWithShell(<SearchResults {...resultsProps(1)} suggestedAirport />);

        fireEvent.click(screen.getByText(/Randomise/));

        expect(routerVisit).toHaveBeenCalledWith(window.location.href, expect.any(Object));
    });

    it('reveals the primary airport TAF on demand', () => {
        renderWithShell(<SearchResults {...resultsProps(1)} />);

        fireEvent.click(screen.getByText('Show TAF'));

        expect(screen.getByText('ENGM TAF TEXT')).not.toBeNull();
    });
});

describe('SearchRoutes', () => {
    const flight = (id, flightIcao, iata, lastSeen) => ({
        id, flightIcao, flightNumber: String(id), airline: { icao: `${iata}X`, iata, name: `${iata} Air` }, aircrafts: 'A320', lastSeen,
    });

    const props = {
        departure: { icao: 'ENGM', name: 'Oslo', isoCountry: 'NO', countryName: 'Norway' },
        arrival: { icao: 'EDDF', name: 'Frankfurt', isoCountry: 'DE', countryName: 'Germany' },
        dataSince: '2026-01-02',
        routes: [flight(1, 'SAS1', 'SK', '2026-10-01'), flight(2, 'DLH2', 'LH', '2026-11-01')],
    };

    it('lists the flights in server order, with a booking menu per flight', () => {
        renderWithShell(<SearchRoutes {...props} />);

        const rows = document.querySelectorAll('tbody tr');
        expect([...rows].map(flightOf)).toEqual(['SAS1', 'DLH2']);
        expect(within(rows[0]).getByText('Simbrief').getAttribute('href')).toContain('orig=ENGM&dest=EDDF&airline=SKX&fltnum=1');
        expect(screen.getByText('Data since 2026-01-02')).not.toBeNull();
    });

    it('sorts by last seen', () => {
        renderWithShell(<SearchRoutes {...props} />);

        fireEvent.click(screen.getByText('Last seen'));
        fireEvent.click(screen.getByText('Last seen'));

        expect(flightOf(document.querySelector('tbody tr'))).toBe('DLH2');
    });
});
