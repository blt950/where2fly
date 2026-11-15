// @vitest-environment jsdom
import { cleanup, fireEvent, render, screen, waitFor } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { MapContext } from '../components/context/MapContext';
import { MapLayoutContext } from '../components/context/MapLayoutContext';
import CardHost from '../components/CardHost';
import { clearRequestCache } from '../components/utils/useCachedPost';

// A failed fetch calls captureException; stub it so the test output stays quiet.
vi.mock('@sentry/react', () => ({ captureException: vi.fn() }));

const AIRPORTS = {
    ENGM: { id: 1, icao: 'ENGM' },
    ESSA: { id: 2, icao: 'ESSA' },
};
const findAirport = (icao) => AIRPORTS[icao];

const airportFixture = {
    airport: {
        icao: 'ENGM',
        name: 'Oslo Gardermoen',
        iso_country: 'NO',
        country: 'Norway',
        runways: [{ id: 1, le_ident: '01L', he_ident: '19R', length_ft: 11811 }],
    },
    metar: 'ENGM 231320Z 27008KT 9999 FEW030 SCT045 03/M02 Q1015',
    taf: null,
    lists: [],
    airlines: [],
    notable: null,
};

const airline = (icao_code, iata_code, name) => ({ id: icao_code, icao_code, iata_code, name });
const SAS = airline('SAS', 'SK', 'Scandinavian');
const DLH = airline('DLH', 'LH', 'Lufthansa');

const flightsFixture = {
    airlines: [DLH, SAS],
    airline: null,
    flights: [
        { id: 1, flight_icao: 'SAS1', flight_number: '1', airline: SAS, aircrafts: [{ icao: 'A320' }], last_seen_at: '2026-11-01T00:00:00Z' },
        { id: 2, flight_icao: 'DLH2', flight_number: '2', airline: DLH, aircrafts: [{ icao: 'B738' }], last_seen_at: '2026-11-01T00:00:00Z', highlighted: true },
    ],
};

const sceneryFixture = {
    MSFS: [{
        id: 1, developer: 'Orbx', payware: 1, link: 'https://x.test', linkDomain: 'x.test',
        fsac: false, cheapestPrice: { EUR: 0 }, ratingAverage: 0,
    }],
};

const baseMapContext = {
    findAirport,
    primaryAirport: null,
    focusAirport: 'ENGM',
    reverseDirection: undefined,
    highlightedAircrafts: [],
    setFocusAirport: vi.fn(),
    setShowAirportIdCard: vi.fn(),
};

const renderCard = (mapContext, airportId = 1) => render(
    <MapContext.Provider value={mapContext}>
        <CardHost airportId={airportId} />
    </MapContext.Provider>,
);

// jsdom 30 has no localStorage by default; SceneryCard reads it directly (not through storage.js).
const memoryStore = () => {
    const data = new Map();

    return {
        getItem: (key) => (data.has(key) ? data.get(key) : null),
        setItem: (key, value) => data.set(key, value),
        removeItem: (key) => data.delete(key),
    };
};

beforeEach(() => {
    clearRequestCache();
    document.head.innerHTML = '<meta name="csrf-token" content="test-token">';
    vi.stubGlobal('localStorage', memoryStore());

    // Route-dependent stub: the two fetches branch on the URL it returns for each name.
    vi.stubGlobal('route', (name, params) => {
        switch (name) {
            case 'api.airport.show': return 'api.airport.show';
            case 'api.airport.scenery': return 'api.airport.scenery';
            case 'front': return `front?icao=${params?.icao}`;
            case 'front.departures': return `front.departures?icao=${params?.icao}`;
            case 'scenery.create': return `scenery.create?airport=${params?.airport}`;
            default: return name;
        }
    });

    vi.stubGlobal('bootstrap', { Tooltip: { getOrCreateInstance: vi.fn(() => ({ dispose: vi.fn() })), getInstance: vi.fn() } });

    vi.stubGlobal('fetch', vi.fn((url) => {
        if (url === 'api.airport.show') {
            return Promise.resolve({ ok: true, status: 200, json: () => Promise.resolve({ data: airportFixture }) });
        }

        if (url === 'api.airport.scenery') {
            return Promise.resolve({ ok: true, status: 200, json: () => Promise.resolve({ data: sceneryFixture }) });
        }

        if (url === 'api.airport.flights') {
            return Promise.resolve({ ok: true, status: 200, json: () => Promise.resolve({ data: flightsFixture }) });
        }

        return Promise.reject(new Error(`unhandled fetch url: ${url}`));
    }));
});

afterEach(() => {
    cleanup();
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
});

describe('CardHost / AirportCard', () => {
    it('shows loading, then fetches and renders the airport', async () => {
        const { container } = renderCard(baseMapContext);

        expect(screen.getByText('Loading')).toBeTruthy();

        await screen.findByText('Oslo Gardermoen');
        expect(container.textContent).toContain('ENGM');

        expect(fetch).toHaveBeenCalledWith('api.airport.show', expect.objectContaining({
            method: 'POST',
            headers: expect.objectContaining({ 'X-CSRF-TOKEN': 'test-token' }),
            body: expect.stringContaining('"secondaryAirport":1'),
        }));
    });

    it('clears both map states on close, so clicking the same marker again reopens the card', async () => {
        const setFocusAirport = vi.fn();
        const setShowAirportIdCard = vi.fn();

        renderCard({ ...baseMapContext, setFocusAirport, setShowAirportIdCard });
        await screen.findByText('Oslo Gardermoen');

        fireEvent.click(screen.getByLabelText('Close airport card'));

        expect(setShowAirportIdCard).toHaveBeenCalledWith(null);
        expect(setFocusAirport).toHaveBeenCalledWith(null);
    });

    it('opens the scenery card and fetches scenery data', async () => {
        renderCard(baseMapContext);
        await screen.findByText('Oslo Gardermoen');

        fireEvent.click(screen.getByText('Scenery'));

        await screen.findByText('Orbx');
        expect(fetch).toHaveBeenCalledWith('api.airport.scenery', expect.objectContaining({
            body: expect.stringContaining('"airportIcao":"ENGM"'),
        }));
    });

    it('opens the scenery card straight away when the scene asks for it', async () => {
        renderCard({ ...baseMapContext, initialCard: 'scenery' });

        await screen.findByText('Orbx');
        expect(fetch).toHaveBeenCalledWith('api.airport.scenery', expect.objectContaining({
            body: expect.stringContaining('"airportIcao":"ENGM"'),
        }));
    });

    it('opens only the airport card by default', async () => {
        renderCard(baseMapContext);
        await screen.findByText('Oslo Gardermoen');

        expect(fetch).not.toHaveBeenCalledWith('api.airport.scenery', expect.anything());
    });

    it('shows Arrival/Departure links when there is no primary airport', async () => {
        renderCard({ ...baseMapContext, primaryAirport: null });
        await screen.findByText('Oslo Gardermoen');

        expect(screen.getByText('Arrival')).toBeTruthy();
        expect(screen.getByText('Departure')).toBeTruthy();
        expect(screen.queryByText(/^Use as/)).toBeNull();
    });

    it('hides Arrival/Departure and shows "Use as ..." when a primary airport is set', async () => {
        renderCard({ ...baseMapContext, primaryAirport: 'ESSA', reverseDirection: false });
        await screen.findByText('Oslo Gardermoen');

        expect(screen.queryByText('Arrival')).toBeNull();
        expect(screen.queryByText('Departure')).toBeNull();
        expect(screen.getByText(/^Use as/)).toBeTruthy();
    });

    describe('flights', () => {
        const onRoute = { ...baseMapContext, primaryAirport: 'ESSA', reverseDirection: false };
        const withAirlines = { ...airportFixture, airlines: [{ ...SAS, highlighted: false }, { ...DLH, highlighted: true }] };
        const flightRows = () => [...document.querySelectorAll('.card-table tbody tr .dropdown-toggle')].map((cell) => cell.textContent);

        beforeEach(() => {
            const bodies = { 'api.airport.show': withAirlines, 'api.airport.flights': flightsFixture, 'api.airport.scenery': sceneryFixture };
            fetch.mockImplementation((url) => Promise.resolve({ ok: true, status: 200, json: () => Promise.resolve({ data: bodies[url] }) }));
        });

        it('opens on the clicked airline, filtering one fetch of every flight on the route', async () => {
            renderCard(onRoute);
            await screen.findByText('Oslo Gardermoen');

            fireEvent.click(screen.getByAltText('See all Lufthansa flights'));

            await screen.findByText('Lufthansa flights');
            expect(flightRows()).toEqual(['DLH2']);

            const [, request] = fetch.mock.calls.find(([url]) => url === 'api.airport.flights');
            expect(JSON.parse(request.body)).toMatchObject({ departureAirportId: 2, arrivalAirportId: 1 });
            expect(JSON.parse(request.body)).not.toHaveProperty('airlineId');
        });

        it('switches airline or shows all without fetching again', async () => {
            renderCard(onRoute);
            await screen.findByText('Oslo Gardermoen');
            fireEvent.click(screen.getByAltText('See all Lufthansa flights'));
            await screen.findByText('Lufthansa flights');

            fireEvent.click(screen.getByText('All'));
            expect(flightRows()).toEqual(['SAS1', 'DLH2']);
            expect(screen.getByRole('heading', { name: 'Flights' })).toBeTruthy();

            fireEvent.click(screen.getByLabelText('Only Scandinavian'));
            expect(flightRows()).toEqual(['SAS1']);

            expect(fetch.mock.calls.filter(([url]) => url === 'api.airport.flights')).toHaveLength(1);
        });

        it('shows the airline logo before each callsign when the route has one airline', async () => {
            const oneAirline = { airlines: [SAS], airline: null, flights: [flightsFixture.flights[0]] };
            fetch.mockImplementation((url) => Promise.resolve({
                ok: true, status: 200, json: () => Promise.resolve({ data: url === 'api.airport.flights' ? oneAirline : withAirlines }),
            }));
            renderCard(onRoute);
            await screen.findByText('Oslo Gardermoen');

            fireEvent.click(screen.getByAltText('See all Scandinavian flights'));
            await screen.findByText('SAS1');

            expect(document.querySelector('.flights-filter')).toBeNull();
            expect(document.querySelector('.card-table tbody tr img.airline-logo').getAttribute('alt')).toBe('Scandinavian logo');
        });

        it('closes the flights and scenery cards when another airport is selected', async () => {
            const { rerender } = renderCard(onRoute);
            await screen.findByText('Oslo Gardermoen');
            fireEvent.click(screen.getByAltText('See all Lufthansa flights'));
            fireEvent.click(screen.getByText('Scenery'));
            await screen.findByText('Lufthansa flights');

            rerender(
                <MapContext.Provider value={{ ...onRoute, focusAirport: 'ESSA' }}>
                    <CardHost airportId={2} />
                </MapContext.Provider>,
            );

            expect(screen.queryByText('Lufthansa flights')).toBeNull();
            expect(document.querySelector('[aria-label="Close scenery card"]')).toBeNull();
        });
    });

    it('opens the search form through the map owner, keeping modified clicks for the browser', async () => {
        const navigate = vi.fn();
        renderCard({ ...baseMapContext, navigate });
        await screen.findByText('Oslo Gardermoen');

        fireEvent.click(screen.getByText('Arrival'));
        expect(navigate).toHaveBeenCalledWith(expect.stringContaining('front?icao=ENGM'));

        fireEvent.click(screen.getByText('Departure'), { ctrlKey: true });
        expect(navigate).toHaveBeenCalledTimes(1);
    });

    describe('on the compact layout', () => {
        const renderTabbed = (mapContext) => {
            const slot = document.createElement('div');
            document.body.append(slot);
            const layout = { compact: true, cardSlot: slot, getInsets: () => ({ top: 0, right: 0, bottom: 0, left: 0 }) };

            render(
                <MapLayoutContext.Provider value={layout}>
                    <MapContext.Provider value={mapContext}>
                        <CardHost airportId={1} />
                    </MapContext.Provider>
                </MapLayoutContext.Provider>,
            );

            return slot;
        };
        // The drawer's own tabs, labelled without their count pill; the scenery card has u-tabs of its own
        const tabs = (slot) => [...slot.querySelectorAll('.card-tabs u-tab')].map((tab) => `${tab.querySelector('.card-tab-label').textContent}:${tab.getAttribute('aria-selected')}`);

        it('renders the cards as tabs inside the drawer, not beside the panel', async () => {
            const slot = renderTabbed(baseMapContext);
            await screen.findByText('Oslo Gardermoen');

            expect(slot.querySelector('.card-tabs')).not.toBeNull();
            expect(document.querySelector('.popup-container')).toBeNull();
            // No route, so no flights tab
            expect(tabs(slot)).toEqual(['ENGM:true', 'Scenery:false']);

            // The scenery count is loaded up front, from the same request the card will use
            await waitFor(() => expect(slot.querySelector('#card-tab-scenery .card-tab-count')?.textContent).toBe('1'));
            expect(fetch.mock.calls.filter(([url]) => url === 'api.airport.scenery')).toHaveLength(1);
        });

        it('counts the flights on a route on its tab, and the card reuses that request', async () => {
            const bodies = { 'api.airport.show': airportFixture, 'api.airport.flights': flightsFixture, 'api.airport.scenery': sceneryFixture };
            fetch.mockImplementation((url) => Promise.resolve({ ok: true, status: 200, json: () => Promise.resolve({ data: bodies[url] }) }));
            const slot = renderTabbed({ ...baseMapContext, primaryAirport: 'ESSA', reverseDirection: false });

            await waitFor(() => expect(slot.querySelector('#card-tab-flights .card-tab-count')?.textContent).toBe('2'));

            fireEvent.click(slot.querySelector('#card-tab-flights'));
            await screen.findByText('SAS1');
            expect(fetch.mock.calls.filter(([url]) => url === 'api.airport.flights')).toHaveLength(1);
        });

        it('counts 0 when the airport has no sceneries (the endpoint answers 404)', async () => {
            fetch.mockImplementation((url) => Promise.resolve(url === 'api.airport.scenery'
                ? { ok: false, status: 404, json: () => Promise.resolve({ message: 'Scenery not found' }) }
                : { ok: true, status: 200, json: () => Promise.resolve({ data: airportFixture }) }));
            const slot = renderTabbed(baseMapContext);

            await waitFor(() => expect(slot.querySelector('#card-tab-scenery .card-tab-count')?.textContent).toBe('0'));

            fireEvent.click(slot.querySelector('#card-tab-scenery'));
            await screen.findByText('No scenery available');
        });

        it('switches cards from the tab strip, the cards leaving out what the tabs cover', async () => {
            const slot = renderTabbed({ ...baseMapContext, primaryAirport: 'ESSA', reverseDirection: false });
            await screen.findByText('Oslo Gardermoen');

            // No close button, no Scenery button: the tabs and the Back arrow cover them
            expect(slot.querySelector('[aria-label="Close airport card"]')).toBeNull();
            expect(screen.queryByText('Scenery', { selector: '.popup-card button' })).toBeNull();

            fireEvent.click(slot.querySelector('#card-tab-scenery'));
            await screen.findByText('Orbx');
            expect(tabs(slot)).toEqual(['ENGM:false', 'Flights:false', 'Scenery:true']);
            expect(screen.queryByText('Oslo Gardermoen')).toBeNull();
            expect(slot.querySelector('[aria-label="Close scenery card"]')).toBeNull();
            expect(screen.queryByRole('heading', { name: 'Scenery' })).toBeNull();

            fireEvent.click(slot.querySelector('#card-tab-airport'));
            expect(tabs(slot)).toEqual(['ENGM:true', 'Flights:false', 'Scenery:false']);
        });

        it('opens on the scenery tab where the scene asks for it, and Back closes everything', async () => {
            const setFocusAirport = vi.fn();
            const setShowAirportIdCard = vi.fn();
            const slot = renderTabbed({ ...baseMapContext, initialCard: 'scenery', setFocusAirport, setShowAirportIdCard });

            await screen.findByText('Orbx');
            expect(tabs(slot)).toEqual(['ENGM:false', 'Scenery:true']);

            fireEvent.click(screen.getByLabelText('Back to the list'));
            expect(setFocusAirport).toHaveBeenCalledWith(null);
            expect(setShowAirportIdCard).toHaveBeenCalledWith(null);
        });
    });
});
