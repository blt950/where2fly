import { isValidElement, useMemo } from 'react';
import { usePage } from '@inertiajs/react';

import Alerts from './Alerts';
import Footer from './Footer';
import MapHint from './MapHint';
import Menu from './Menu';
import { ShellMapContext } from './ShellMapContext';
import MapView from '../map/MapView';
import { SCENE_DEFAULTS } from '../map/mapScene';
import { useMapState } from '../map/useMapState';

// The persistent layout: same DOM as layouts/app.blade.php so the existing SCSS applies, but it
// is never thrown away between visits, so neither is the map.
const AppShell = ({ children }) => {

    const page = usePage();
    const Page = isValidElement(children) ? children.type : null;

    // Pages declare their map as a static `mapScene(props)`, read here during render so the
    // first visit constructs the map with the right camera instead of correcting it afterwards.
    const scene = useMemo(
        () => Page?.mapScene?.(page.props) ?? SCENE_DEFAULTS,
        // eslint-disable-next-line react-hooks/exhaustive-deps
        [page.url],
    );
    const [state, actions] = useMapState(scene, page.url);

    const shellMap = useMemo(() => ({
        focusAirport: state.focusAirport,
        setFocusAirport: actions.setFocusAirport,
        pingAirport: actions.pingAirport,
    }), [state.focusAirport, actions]);

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
                        <MapView state={state} actions={actions} sceneKey={page.url} />
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
