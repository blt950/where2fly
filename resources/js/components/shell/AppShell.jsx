import { isValidElement, useCallback, useEffect, useLayoutEffect, useMemo, useState } from 'react';
import { router, usePage } from '@inertiajs/react';

import Alerts from './Alerts';
import BottomNav from './BottomNav';
import Drawer from './Drawer';
import Footer from './Footer';
import MapHint from './MapHint';
import Menu from './Menu';
import { ShellMapContext } from './ShellMapContext';
import { useLayoutMode } from './useCompactLayout';
import { useDrawerStop } from './useDrawerStop';
import { useMapInsets } from './useMapInsets';
import { usePageAnnouncement } from './usePageAnnouncement';
import { useSelection } from './useSelection';
import { useShellScene } from './useShellScene';
import { MapLayoutContext } from '../context/MapLayoutContext';
import MapView from '../map/MapView';
import { useMapState } from '../map/useMapState';
import { installTooltips } from '../utils/tooltips';

const visit = (url) => router.visit(url);

// The persistent layout around every Inertia page. It is never thrown away between visits, so
// neither is the map.
const AppShell = ({ children }) => {

    const page = usePage();
    const Page = isValidElement(children) ? children.type : null;

    const scene = useShellScene(Page, page.props);
    const [state, actions] = useMapState(scene);
    const selectInUrl = useSelection(page, state, actions);

    // Layout: the compact one moves the panel into a drawer over a full-screen map ('sheet'), or
    // beside it on a phone turned on its side ('side')
    const mode = useLayoutMode();
    const compact = mode !== 'panel';
    const cardsOpen = Boolean(state.showAirportIdCard);
    const { stop, setStop, raiseDrawer, onBackgroundClick } = useDrawerStop({
        mode, Page, sceneVersion: state.sceneVersion, selectedCard: state.showAirportIdCard ?? null,
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
    const { getInsets, onCoverChange, registerCards, registerFooter, registerHeader, mapRef } = useMapInsets(mode);
    const [cardSlot, setCardSlot] = useState(null);

    // For the CSS only; the hooks get `mode` itself
    useLayoutEffect(() => {
        document.body.classList.toggle('shell-compact', compact);
        document.body.classList.toggle('shell-side', mode === 'side');
    }, [compact, mode]);

    // A `wide` page (feedback) shows its main content over the map area on wide screens; the map
    // stays mounted underneath. On the compact layout that content just sits in the drawer.
    const wide = Boolean(Page?.wide) && !compact;
    const [wideSlot, setWideSlot] = useState(null);

    const layout = useMemo(() => ({ compact, getInsets, cardSlot, wideSlot: wide ? wideSlot : null, registerCards, registerFooter, registerHeader }),
        [compact, getInsets, cardSlot, wide, wideSlot, registerCards, registerFooter, registerHeader]);

    const announcement = usePageAnnouncement();

    useEffect(installTooltips, []);

    // An airport the map couldn't find (a typo in ?airport=, the Scenery search box), said once in
    // the alerts on any page; gone with the next page or the next airport found
    const [missingAirport, setMissingAirport] = useState(null);
    const onAirportNotFound = useCallback((icao) => setMissingAirport(icao), []);
    const [missingFor, setMissingFor] = useState({ component: page.component, focus: state.focusAirport });
    if (page.component !== missingFor.component || (state.focusAirport && state.focusAirport !== missingFor.focus)) {
        setMissingFor({ component: page.component, focus: state.focusAirport });
        setMissingAirport(null);
    }
    const notices = missingAirport
        ? [{ key: 'airport-not-found', variant: 'warning', text: `No airport found for "${missingAirport}". Check the ICAO code and try again.` }]
        : [];

    const shellMap = useMemo(() => ({
        focusAirport: state.focusAirport,
        setFocusAirport: select,
        pingAirport: actions.pingAirport,
    }), [state.focusAirport, select, actions]);

    return (
        <ShellMapContext.Provider value={shellMap}>
            <MapLayoutContext.Provider value={layout}>
                <Menu />

                <div className="d-flex flex-row shell-stage">
                    <Drawer mode={mode} stop={stop} onStopChange={setStop} onCoverChange={onCoverChange}>
                        {/* Kept mounted while the cards show, so the page keeps its state and scroll */}
                        <div className="drawer-page" hidden={compact && cardsOpen}>
                            <Alerts notices={notices} />
                            {children}
                        </div>
                        <div ref={setCardSlot} className="drawer-cards" hidden={!compact || !cardsOpen}></div>
                    </Drawer>

                    <div className="shell-map">
                        <aside id="map" ref={mapRef}>
                            <MapView state={state} actions={mapActions} onAirportNotFound={onAirportNotFound}
                                onBackgroundClick={onBackgroundClick} navigate={visit} listsRevision={page.props.listsRevision}
                                authenticated={Boolean(page.props.nav?.account)} />
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
