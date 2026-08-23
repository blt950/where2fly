// @vitest-environment jsdom
import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { MapContext } from '../components/context/MapContext';
import PopupContainer from '../components/PopupContainer';

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
        <PopupContainer airportId={airportId} />
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

    vi.stubGlobal('bootstrap', { Tooltip: { getOrCreateInstance: vi.fn(), getInstance: vi.fn() } });

    vi.stubGlobal('fetch', vi.fn((url) => {
        if (url === 'api.airport.show') {
            return Promise.resolve({ json: () => Promise.resolve({ data: airportFixture }) });
        }

        if (url === 'api.airport.scenery') {
            return Promise.resolve({ json: () => Promise.resolve({ data: sceneryFixture }) });
        }

        return Promise.reject(new Error(`unhandled fetch url: ${url}`));
    }));
});

afterEach(() => {
    cleanup();
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
});

describe('PopupContainer / AirportCard', () => {
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

    it('renders no card content when airportId is null', () => {
        const { container } = renderCard(baseMapContext, null);

        expect(container.querySelector('.popup-card')).toBeNull();
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
});
