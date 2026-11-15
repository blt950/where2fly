import { isValidElement, useCallback, useLayoutEffect, useMemo, useState } from 'react';
import { router, usePage } from '@inertiajs/react';

import Alerts from './Alerts';
import BottomNav from './BottomNav';
import Drawer from './Drawer';
import Footer from './Footer';
import MapHint from './MapHint';
import Menu from './Menu';
import { ShellMapContext } from './ShellMapContext';
import { useCompactLayout } from './useCompactLayout';
import { useDrawerStop } from './useDrawerStop';
import { useMapInsets } from './useMapInsets';
import { usePageAnnouncement } from './usePageAnnouncement';
import { useSelection } from './useSelection';
import { useShellScene } from './useShellScene';
import { MapLayoutContext } from '../context/MapLayoutContext';
import MapView from '../map/MapView';
import { useMapState } from '../map/useMapState';

const visit = (url) => router.visit(url);

// The persistent layout: same DOM as layouts/app.blade.php so the existing SCSS applies, but it
// is never thrown away between visits, so neither is the map.
const AppShell = ({ children }) => {

    const page = usePage();
    const Page = isValidElement(children) ? children.type : null;

    const scene = useShellScene(Page, page.props);
    const [state, actions] = useMapState(scene);
    const selectInUrl = useSelection(page, state, actions);

    // Layout: the compact one moves the panel into a drawer over a full-screen map
    const compact = useCompactLayout();
    const cardsOpen = Boolean(state.showAirportIdCard);
    const { stop, setStop, raiseDrawer, onBackgroundClick } = useDrawerStop({
        compact, Page, sceneVersion: state.sceneVersion, selectedCard: state.showAirportIdCard ?? null,
    });

    // Selecting an airport also brings a collapsed drawer back, even the airport already selected,
    // whose URL (and so card) doesn't change
    const select = useCallback((icao) => {
        if (icao) {
            raiseDrawer();
        }
        selectInUrl(icao);
    }, [raiseDrawer, selectInUrl]);
    const mapActions = useMemo(() => ({ ...actions, setFocusAirport: select }), [actions, select]);
    const { getInsets, onDrawerVisible } = useMapInsets();
    const [cardSlot, setCardSlot] = useState(null);

    useLayoutEffect(() => {
        document.body.classList.toggle('shell-compact', compact);
    }, [compact]);

    // A `wide` page (feedback) shows its main content over the map area on wide screens; the map
    // stays mounted underneath. On the compact layout that content just sits in the drawer.
    const wide = Boolean(Page?.wide) && !compact;
    const [wideSlot, setWideSlot] = useState(null);

    const layout = useMemo(() => ({ compact, getInsets, cardSlot, wideSlot: wide ? wideSlot : null }), [compact, getInsets, cardSlot, wide, wideSlot]);

    const announcement = usePageAnnouncement();

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
                                onBackgroundClick={onBackgroundClick} navigate={visit} listsRevision={page.props.listsRevision} />
                        </aside>

                        {/* Only plays on the first, full load: the shell, and so this element, persists. */}
                        <div className="map-reveal" aria-hidden="true"></div>

                        {wide && <div ref={setWideSlot} className="shell-wide" scroll-region=""></div>}

                        {Page?.showMapHint !== false && !wide && <MapHint />}
                        <Footer />
                    </div>
                </div>

                {compact && <BottomNav onNavigate={raiseDrawer} />}

                <div className="visually-hidden" aria-live="polite" aria-atomic="true">{announcement}</div>
            </MapLayoutContext.Provider>
        </ShellMapContext.Provider>
    );
};

export default AppShell;
