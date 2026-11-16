// @vitest-environment jsdom
import { cleanup, fireEvent, render, screen, within } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { ShellMapContext } from '../components/shell/ShellMapContext';

const routerVisit = vi.fn();
const routerPost = vi.fn();

vi.mock('@inertiajs/react', () => ({
    Head: () => null,
    // eslint-disable-next-line no-unused-vars
    Link: ({ children, preserveScroll, prefetch, ...props }) => <a {...props}>{children}</a>,
    router: { visit: (...args) => routerVisit(...args), post: (...args) => routerPost(...args) },
}));

const { default: SearchResults } = await import('../pages/SearchResults');
const { default: SearchRoutes } = await import('../pages/SearchRoutes');
const { default: Scenery } = await import('../pages/Scenery');
const { default: Top } = await import('../pages/Top');

const airport = (icao, rank, extra = {}) => ({
    icao, rank, name: `${icao} Airport`, isoCountry: 'NO', countryName: 'Norway',
    scoreCount: 0, distance: 100 * rank, airtime: rank, airtimeLabel: `${rank}:00`,
    scores: [],
    ...extra,
});

const resultsProps = (count) => ({
    query: { icao: 'ENGM', direction: 'departure', scores: { METAR_WINDY: '1' }, sortByWeather: '0', sortByATC: '1' },
    direction: 'departure',
    suggestedAirport: false,
    bearingWarning: null,
    searchVersionWarning: null,
    noInterestingScores: false,
    primaryAirport: { ...airport('ENGM', 0), longestRunwayFt: 11811, metar: 'ENGM METAR', taf: 'ENGM TAF TEXT' },
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
});

afterEach(() => {
    cleanup();
    vi.unstubAllGlobals();
    routerVisit.mockClear();
    routerPost.mockClear();
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

    it('carries the airtime in the distance cell too, for the phone layout that folds the Time column', () => {
        renderWithShell(<SearchResults {...resultsProps(1)} />);
        const cells = document.querySelectorAll('tr[data-airport-icao] td');

        expect(cells[1].textContent).toBe('100nm1:00h');
        expect(cells[1].querySelector('.results-time-folded').textContent).toBe('1:00h');
        expect(cells[2].className).toBe('results-time');
    });

    it('focuses the airport on click; the card shows its details', () => {
        const { shell } = renderWithShell(<SearchResults {...resultsProps(3)} />);

        fireEvent.click(document.querySelector('tr[data-airport-icao="EA02"]'));

        expect(shell.setFocusAirport).toHaveBeenCalledWith('EA02');
        expect(document.querySelector('tr[data-detail-row]')).toBeNull();
    });

    it('unfolds the rows when the map focuses an airport behind "Show more"', () => {
        renderWithShell(<SearchResults {...resultsProps(14)} />, { focusAirport: 'EA13' });

        expect(hiddenRows()).toBe(0);
        expect(document.querySelector('tr.active').dataset.airportIcao).toBe('EA13');
    });

    it('posts the query back to the form for editing as the server sent it', () => {
        renderWithShell(<SearchResults {...resultsProps(1)} />);

        fireEvent.click(screen.getByText(/Edit filters/));

        expect(routerPost).toHaveBeenCalledWith('search.edit', resultsProps(1).query);
    });

    it('randomises with a fresh visit to the same search, without the selected airport', () => {
        window.history.replaceState(null, '', '/search?icao=ENGM&airport=ESSA');
        renderWithShell(<SearchResults {...resultsProps(1)} suggestedAirport />);

        fireEvent.click(screen.getByText(/Randomise/));

        expect(routerVisit).toHaveBeenCalledWith('/search?icao=ENGM', expect.any(Object));
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

describe('Top', () => {
    const topProps = (extra = {}) => ({
        airports: [airport('ENGM', 1), airport('ESSA', 2, { scores: [] }), airport('EKCH', 3)],
        continent: 'EU', exclude: null, aircraft: 'JM',
        continents: { EU: 'Europe', NA: 'North America' },
        aircraftTypes: { JM: { name: 'Narrow Body' }, JH: { name: 'Wide Body' } },
        scoreTypes: {},
        ...extra,
    });
    const rows = () => [...document.querySelectorAll('tbody tr')].map((row) => row.querySelector('th').textContent);

    it('focuses and pings the airport of a clicked row', () => {
        const { shell } = renderWithShell(<Top {...topProps()} />);

        fireEvent.click(screen.getByText('ESSA Airport'));

        expect(shell.setFocusAirport).toHaveBeenCalledWith('ESSA');
        expect(shell.pingAirport).toHaveBeenCalledWith('ESSA');
    });

    it('sorts by a column, then the other way on a second click', () => {
        renderWithShell(<Top {...topProps()} />);
        const header = screen.getByRole('columnheader', { name: 'Airport' });

        fireEvent.click(header);
        expect(rows()).toEqual(['3', '1', '2']);
        expect(header.getAttribute('aria-sort')).toBe('ascending');

        fireEvent.click(header);
        expect(rows()).toEqual(['2', '1', '3']);
    });

    it('links each filter to a URL that keeps the other two', () => {
        renderWithShell(<Top {...topProps({ exclude: 'vatsim' })} />);

        const hrefs = [...document.querySelectorAll('a.dropdown-item')].map((link) => link.getAttribute('href'));

        expect(hrefs).toContain('top.filtered?continent=NA&exclude=vatsim&aircraft=JM');
        // "No aircraft filter" travels as aircraft=all: an absent param means the default type
        expect(hrefs).toContain('top.filtered?continent=EU&exclude=vatsim&aircraft=all');
        // No continent: the unfiltered route, the other two kept
        expect(hrefs).toContain('top?exclude=vatsim&aircraft=JM');
        expect(screen.getByRole('link', { name: 'Included' }).getAttribute('href')).toBe('top.filtered?continent=EU&aircraft=JM');
    });

    it('brings a row the map focused into view, unless it is already', () => {
        const scrollIntoView = vi.fn();
        Element.prototype.scrollIntoView = scrollIntoView;
        const rect = vi.spyOn(Element.prototype, 'getBoundingClientRect');

        rect.mockReturnValue({ top: 2000, bottom: 2040 });
        const { rerender, shell } = renderWithShell(<Top {...topProps()} />, { focusAirport: 'EKCH' });
        expect(scrollIntoView).toHaveBeenCalledTimes(1);

        rect.mockReturnValue({ top: 100, bottom: 140 });
        rerender(<ShellMapContext.Provider value={{ ...shell, focusAirport: 'ESSA' }}><Top {...topProps()} /></ShellMapContext.Provider>);
        expect(scrollIntoView).toHaveBeenCalledTimes(1);

        rect.mockRestore();
        delete Element.prototype.scrollIntoView;
    });

    it('says so when there are no airports', () => {
        renderWithShell(<Top {...topProps({ airports: [] })} />);

        expect(screen.getByText(/No top airports available/)).toBeTruthy();
    });
});

describe('Scenery', () => {
    it('focuses the airport typed into the search box, upper-cased', () => {
        const { shell } = renderWithShell(<Scenery simulators={['MSFS2024', 'XP12']} filteredSimulator="XP12" />);

        fireEvent.change(screen.getByLabelText(/Search for airport/), { target: { value: 'engm' } });
        fireEvent.submit(screen.getByLabelText(/Search for airport/).closest('form'));

        expect(shell.setFocusAirport).toHaveBeenCalledWith('ENGM');
        expect(screen.getByRole('link', { name: 'XP12' }).className).toContain('btn-primary');
        expect(screen.getByRole('link', { name: 'All' }).className).toContain('btn-outline-primary');
    });

    it('starts the map empty until the deferred airports arrive, each opening its scenery card', () => {
        expect(Scenery.mapScene({})).toMatchObject({ airports: {}, initialCard: 'scenery', showLists: false });
        expect(Scenery.mapScene({ airportMapData: { ENGM: { id: 1 } } }).airports).toEqual({ ENGM: { id: 1 } });
    });
});
