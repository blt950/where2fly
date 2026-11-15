import { isValidElement, useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { router, usePage } from '@inertiajs/react';

import Alerts from './Alerts';
import Footer from './Footer';
import MapHint from './MapHint';
import Menu from './Menu';
import { ShellMapContext } from './ShellMapContext';
import { selectedAirportIn, withSelectedAirport } from './selection';
import MapView from '../map/MapView';
import { SCENE_DEFAULTS } from '../map/mapScene';
import { useMapState } from '../map/useMapState';

const visit = (url) => router.visit(url);

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
            <Menu />

            <div className="d-flex flex-row">
                <main>
                    <Alerts />
                    {children}
                </main>

                <div>
                    <aside id="map">
                        <MapView state={state} actions={mapActions} onAirportNotFound={onAirportNotFound} navigate={visit} />
                    </aside>

                    {/* Only plays on the first, full load: the shell, and so this element, persists. */}
                    <div className="map-reveal" aria-hidden="true"></div>

                    {Page?.showMapHint !== false && <MapHint />}
                    <Footer />
                </div>
            </div>
        </ShellMapContext.Provider>
    );
};

export default AppShell;
