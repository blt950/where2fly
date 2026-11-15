// @vitest-environment jsdom
import { useEffect } from 'react';
import { act, cleanup, render } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

let page = { url: '/top', props: {} };
const mapViewMounts = vi.fn();
const mapViewRenders = [];
const router = { push: vi.fn(), replace: vi.fn(), visit: vi.fn() };
let mapProps;

vi.mock('@inertiajs/react', () => ({ usePage: () => page, router }));
vi.mock('../components/shell/Menu', () => ({ default: () => null }));
vi.mock('../components/shell/Alerts', () => ({ default: () => null }));
vi.mock('../components/shell/Footer', () => ({ default: () => null }));
vi.mock('../components/shell/MapHint', () => ({ default: () => <div data-testid="hint" /> }));
vi.mock('../components/map/MapView', () => ({
    default: function MapViewStub(props) {
        mapProps = props;
        mapViewRenders.push({ version: props.state.sceneVersion, camera: props.state.camera, focus: props.state.focusAirport, card: props.state.showAirportIdCard });
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

// Inertia hands the layout a new page object on every visit, and new props unless a client-side
// visit keeps them
const visit = (url, props = {}) => { page = { url, props }; };
const last = () => mapViewRenders.at(-1);

beforeEach(() => {
    window.history.replaceState(null, '', '/');
});

afterEach(() => {
    cleanup();
    mapViewMounts.mockClear();
    mapViewRenders.length = 0;
    Object.values(router).forEach((fn) => fn.mockClear());
});

describe('AppShell', () => {
    it('keeps one map across visits and hands it each page\'s scene', () => {
        visit('/top', { continent: null });
        const { rerender } = render(<AppShell><TopPage {...page.props} /></AppShell>);

        visit('/top/EU', { continent: 'EU' });
        rerender(<AppShell><TopPage {...page.props} /></AppShell>);

        expect(mapViewMounts).toHaveBeenCalledTimes(1);
        expect(last()).toMatchObject({ camera: EUROPE, version: 2 });
    });

    it('starts a new scene only when its content changes, not on every visit', () => {
        visit('/top/EU', { continent: 'EU' });
        const { rerender } = render(<AppShell><TopPage {...page.props} /></AppShell>);

        // A restored history entry: new props objects, same content
        visit('/top/EU', { continent: 'EU' });
        rerender(<AppShell><TopPage {...page.props} /></AppShell>);
        expect(last().version).toBe(1);

        visit('/top/EU', { continent: null });
        rerender(<AppShell><TopPage {...page.props} /></AppShell>);
        expect(last().version).toBe(2);
    });

    it('leaves the map as it was for a page that keeps it', () => {
        const TextPage = () => null;
        TextPage.keepsMap = true;

        visit('/top/EU', { continent: 'EU' });
        const { rerender } = render(<AppShell><TopPage {...page.props} /></AppShell>);

        visit('/changelog', { html: '<p>notes</p>' });
        rerender(<AppShell><TextPage {...page.props} /></AppShell>);

        expect(last()).toMatchObject({ camera: EUROPE, version: 1 });
    });

    it('falls back to the default scene for pages without one', () => {
        visit('/elsewhere');
        render(<AppShell><OtherPage /></AppShell>);

        expect(last().camera).toEqual({ type: 'saved' });
    });

    it('shows the map hint unless the page opts out', () => {
        visit('/top');
        const top = render(<AppShell><TopPage /></AppShell>);
        expect(top.queryByTestId('hint')).toBeNull();
        cleanup();

        visit('/elsewhere');
        const other = render(<AppShell><OtherPage /></AppShell>);
        expect(other.queryByTestId('hint')).not.toBeNull();
    });

    describe('the selected airport', () => {
        it('comes from the URL, so a link opens it and Back clears it', () => {
            visit('/top?airport=ESSA', { continent: null });
            const { rerender } = render(<AppShell><TopPage {...page.props} /></AppShell>);
            expect(last().focus).toBe('ESSA');

            // The map opens the card for the focused airport
            act(() => mapProps.actions.set({ showAirportIdCard: 7 }));

            // Back: the previous entry, same scene, no selection, so the card closes too
            visit('/top', { continent: null });
            rerender(<AppShell><TopPage {...page.props} /></AppShell>);
            expect(last()).toMatchObject({ focus: null, card: null, version: 1 });
        });

        it('is written to the URL: opening pushes a history entry, switching and closing replace it', () => {
            visit('/top?continent=x&b%5B%5D=1');
            const { rerender } = render(<AppShell><TopPage /></AppShell>);

            act(() => mapProps.actions.setFocusAirport('ESSA'));
            expect(router.push).toHaveBeenCalledWith(expect.objectContaining({ url: '/top?continent=x&b%5B%5D=1&airport=ESSA', preserveState: true }));

            visit('/top?continent=x&b%5B%5D=1&airport=ESSA');
            rerender(<AppShell><TopPage /></AppShell>);

            act(() => mapProps.actions.setFocusAirport('ENGM'));
            expect(router.replace).toHaveBeenLastCalledWith(expect.objectContaining({ url: '/top?continent=x&b%5B%5D=1&airport=ENGM' }));

            act(() => mapProps.actions.setFocusAirport(null));
            expect(router.replace).toHaveBeenLastCalledWith(expect.objectContaining({ url: '/top?continent=x&b%5B%5D=1' }));
            expect(router.push).toHaveBeenCalledTimes(1);
        });

        it('is the same selection pages get from the shell context', () => {
            let shell;
            const Probe = () => { shell = useShellMap(); return null; };

            visit('/top?airport=ESSA');
            render(<AppShell><Probe /></AppShell>);

            expect(shell.focusAirport).toBe('ESSA');
            act(() => shell.setFocusAirport('ENGM'));
            expect(router.replace).toHaveBeenCalledWith(expect.objectContaining({ url: '/top?airport=ENGM' }));
        });
    });

    it('announces a new page and moves focus to the panel, but not for a filter or a selection', async () => {
        const live = () => document.querySelector('[aria-live="polite"]').textContent;
        const settle = () => new Promise((resolve) => setTimeout(resolve, 0));
        document.title = 'Where2Fly | Top List';

        visit('/top', { continent: null });
        const { rerender } = render(<AppShell><TopPage {...page.props} /></AppShell>);

        // Same page, same title: a filter and a selection
        visit('/top/EU?airport=ESSA', { continent: 'EU' });
        rerender(<AppShell><TopPage {...page.props} /></AppShell>);
        document.title = 'Where2Fly | Top List';
        await settle();
        expect(live()).toBe('');

        // The next page's <Head> sets its title after the visit has rendered
        visit('/changelog');
        rerender(<AppShell><OtherPage /></AppShell>);
        document.title = 'Where2Fly | Changelog';
        await vi.waitFor(() => expect(live()).toBe('Where2Fly | Changelog'));
        expect(document.activeElement.tagName).toBe('MAIN');
    });

    it('tells the page about an airport the map could not find, stamped per miss', () => {
        const seen = [];
        const ScenerySearch = () => {
            seen.push(useShellMap().airportNotFound);
            return null;
        };

        visit('/scenery');
        render(<AppShell><ScenerySearch /></AppShell>);

        act(() => mapProps.onAirportNotFound('ZZZZ'));
        const first = seen.at(-1);
        act(() => mapProps.onAirportNotFound('ZZZZ'));

        expect(first.icao).toBe('ZZZZ');
        expect(seen.at(-1)).not.toBe(first);
    });
});
