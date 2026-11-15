// @vitest-environment jsdom
import { useEffect } from 'react';
import { act, cleanup, render } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const page = { url: '/top', props: {} };
const mapViewMounts = vi.fn();
const mapViewRenders = [];

vi.mock('@inertiajs/react', () => ({ usePage: () => page }));
vi.mock('../components/shell/Menu', () => ({ default: () => null }));
vi.mock('../components/shell/Alerts', () => ({ default: () => null }));
vi.mock('../components/shell/Footer', () => ({ default: () => null }));
vi.mock('../components/shell/MapHint', () => ({ default: () => <div data-testid="hint" /> }));
let reportNotFound;

vi.mock('../components/map/MapView', () => ({
    default: function MapViewStub({ state, sceneKey, onAirportNotFound }) {
        reportNotFound = onAirportNotFound;
        mapViewRenders.push({ sceneKey, stateKey: state.sceneKey, camera: state.camera, focus: state.focusAirport });
        useEffect(() => { mapViewMounts(); }, []);
        return null;
    },
}));

const { default: AppShell } = await import('../components/shell/AppShell');
const { useShellMap } = await import('../components/shell/ShellMapContext');

const WORLD = { type: 'view', view: { center: [0, 30], zoom: 2 } };
const EUROPE = { type: 'view', view: { center: [15, 55], zoom: 4 } };

const TopPage = () => null;
TopPage.mapScene = ({ continent }) => ({ camera: continent ? EUROPE : WORLD });
TopPage.showMapHint = false;

const OtherPage = () => null;

const visit = (url, props) => {
    page.url = url;
    page.props = props;
};

afterEach(cleanup);

beforeEach(() => {
    mapViewMounts.mockClear();
    mapViewRenders.length = 0;
});

describe('AppShell', () => {
    it('keeps one map across visits and hands it each page\'s scene with its key', () => {
        visit('/top', { continent: null });
        const { rerender } = render(<AppShell><TopPage {...page.props} /></AppShell>);

        visit('/top/EU', { continent: 'EU' });
        rerender(<AppShell><TopPage {...page.props} /></AppShell>);

        expect(mapViewMounts).toHaveBeenCalledTimes(1);
        // Never a render that pairs the new visit with the previous scene
        mapViewRenders.forEach((r) => expect(r.stateKey).toBe(r.sceneKey));
        expect(mapViewRenders.at(-1)).toMatchObject({ sceneKey: '/top/EU', camera: EUROPE });
    });

    it('falls back to the default scene for pages without one', () => {
        visit('/elsewhere', {});
        render(<AppShell><OtherPage /></AppShell>);

        expect(mapViewRenders.at(-1).camera).toEqual({ type: 'saved' });
    });

    it('shows the map hint unless the page opts out', () => {
        visit('/top', {});
        const top = render(<AppShell><TopPage /></AppShell>);
        expect(top.queryByTestId('hint')).toBeNull();
        cleanup();

        visit('/elsewhere', {});
        const other = render(<AppShell><OtherPage /></AppShell>);
        expect(other.queryByTestId('hint')).not.toBeNull();
    });

    it('tells the page about an airport the map could not find, stamped per miss', () => {
        const seen = [];
        const ScenerySearch = () => {
            seen.push(useShellMap().airportNotFound);
            return null;
        };

        visit('/scenery', {});
        render(<AppShell><ScenerySearch /></AppShell>);

        act(() => reportNotFound('ZZZZ'));
        const first = seen.at(-1);
        act(() => reportNotFound('ZZZZ'));

        expect(first.icao).toBe('ZZZZ');
        expect(seen.at(-1)).not.toBe(first);
    });
});
