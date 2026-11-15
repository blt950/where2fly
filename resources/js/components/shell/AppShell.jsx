import { isValidElement, useCallback, useEffect, useLayoutEffect, useMemo, useRef, useState } from 'react';
import { router, usePage } from '@inertiajs/react';

import Alerts from './Alerts';
import BottomNav from './BottomNav';
import Drawer from './Drawer';
import Footer from './Footer';
import MapHint from './MapHint';
import Menu from './Menu';
import { ShellMapContext } from './ShellMapContext';
import { selectedAirportIn, withSelectedAirport } from './selection';
import { useCompactLayout } from './useCompactLayout';
import { MapLayoutContext } from '../context/MapLayoutContext';
import MapView from '../map/MapView';
import { SCENE_DEFAULTS } from '../map/mapScene';
import { useMapState } from '../map/useMapState';

const visit = (url) => router.visit(url);
const FOOTER_HEIGHT = 30;

// What the card stack beside the panel covers of the map, measured when the camera asks
const cardStackWidth = () => {
    const stack = document.querySelector('.popup-container');
    const map = document.querySelector('#map .maplibregl-map');

    if (!stack || !map || !stack.querySelector('.popup-card')) {
        return 0;
    }

    return Math.max(0, stack.getBoundingClientRect().right - map.getBoundingClientRect().left);
};

// The persistent layout: same DOM as layouts/app.blade.php so the existing SCSS applies, but it
// is never thrown away between visits, so neither is the map.
const AppShell = ({ children }) => {

    const page = usePage();
    const Page = isValidElement(children) ? children.type : null;

    // Pages declare their map as a static `mapScene(props)`, read here during render so the
    // first visit constructs the map with the right camera instead of correcting it afterwards.
    const scene = useMemo(() => Page?.mapScene?.(page.props) ?? SCENE_DEFAULTS, [Page, page.props]);
    const [state, actions] = useMapState(scene);

    // Selecting an airport writes the URL; the URL drives the map. Opening a card adds a history
    // entry, so Back closes it; switching airports or closing replaces the entry instead.
    const selected = selectedAirportIn(page.url);
    const pageUrl = useRef(page.url);
    pageUrl.current = page.url;

    const select = useCallback((icao) => {
        const current = selectedAirportIn(pageUrl.current);
        const next = icao || null;

        if (next === current) {
            return;
        }

        const method = current === null ? 'push' : 'replace';
        router[method]({ url: withSelectedAirport(pageUrl.current, next), preserveState: true, preserveScroll: true });
    }, []);

    // Also re-applied after a new scene, which starts with nothing focused. No selection closes
    // the cards as well, as their close button does.
    useEffect(() => {
        if ((state.focusAirport ?? null) !== selected) {
            actions.set(selected ? { focusAirport: selected } : { focusAirport: null, showAirportIdCard: null });
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [selected, state.sceneVersion]);

    const mapActions = useMemo(() => ({ ...actions, setFocusAirport: select }), [actions, select]);

    // Layout: the compact one moves the panel into a drawer over a full-screen map
    const compact = useCompactLayout();
    const [stop, setStop] = useState('half');
    const [cardSlot, setCardSlot] = useState(null);
    const drawerVisible = useRef(0);
    const onDrawerVisible = useCallback((px) => { drawerVisible.current = px; }, []);
    const cardsOpen = Boolean(state.showAirportIdCard);

    useLayoutEffect(() => {
        document.body.classList.toggle('shell-compact', compact);
    }, [compact]);

    const getInsets = useCallback(() => (document.body.classList.contains('shell-compact')
        ? { top: 0, right: 0, bottom: drawerVisible.current, left: 0 }
        : { top: 0, right: 0, bottom: FOOTER_HEIGHT, left: cardStackWidth() }), []);

    const layout = useMemo(() => ({ compact, getInsets, cardSlot }), [compact, getInsets, cardSlot]);

    // Selecting something, or moving to another page, brings a collapsed drawer back
    useEffect(() => {
        if (compact && cardsOpen) {
            setStop((current) => (current === 'peek' ? 'half' : current));
        }
    }, [compact, cardsOpen]);

    const raiseDrawer = useCallback(() => setStop((current) => (current === 'peek' ? 'half' : current)), []);

    useEffect(raiseDrawer, [Page, raiseDrawer]);

    // Tapping the map itself gets the drawer out of the way
    const onBackgroundClick = useCallback(() => {
        if (document.body.classList.contains('shell-compact')) {
            setStop('peek');
        }
    }, []);

    // Stamped, so a page can tell a repeat miss on the same ICAO from the one it already showed
    const [airportNotFound, setAirportNotFound] = useState(null);
    const onAirportNotFound = useCallback((icao) => setAirportNotFound({ icao, at: Date.now() }), []);

    const shellMap = useMemo(() => ({
        focusAirport: state.focusAirport,
        setFocusAirport: select,
        pingAirport: actions.pingAirport,
        airportNotFound,
    }), [state.focusAirport, select, actions, airportNotFound]);

    return (
        <ShellMapContext.Provider value={shellMap}>
            <MapLayoutContext.Provider value={layout}>
                <Menu />

                <div className="d-flex flex-row shell-stage">
                    <Drawer enabled={compact} stop={stop} onStopChange={setStop} onVisibleChange={onDrawerVisible}>
                        {/* Kept mounted while the cards show, so the page keeps its state and scroll */}
                        <div className="drawer-page" hidden={compact && cardsOpen}>
                            <Alerts />
                            {children}
                        </div>
                        <div ref={setCardSlot} className="drawer-cards" hidden={!compact || !cardsOpen}></div>
                    </Drawer>

                    <div className="shell-map">
                        <aside id="map">
                            <MapView state={state} actions={mapActions} onAirportNotFound={onAirportNotFound}
                                onBackgroundClick={onBackgroundClick} navigate={visit} />
                        </aside>

                        {/* Only plays on the first, full load: the shell, and so this element, persists. */}
                        <div className="map-reveal" aria-hidden="true"></div>

                        {Page?.showMapHint !== false && <MapHint />}
                        <Footer />
                    </div>
                </div>

                {compact && <BottomNav onNavigate={raiseDrawer} />}
            </MapLayoutContext.Provider>
        </ShellMapContext.Provider>
    );
};

export default AppShell;
