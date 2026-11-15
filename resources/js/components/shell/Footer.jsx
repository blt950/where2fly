import { Fragment, useState } from 'react';
import { usePage } from '@inertiajs/react';

import NavItem from './NavItem';
import { useMapLayout } from '../context/MapLayoutContext';

// The links come from NavigationHelper (`nav.info`). On the compact layout the credits sit behind an
// ⓘ under the layers control, as MapLibre does on compact maps: reachable at every stop showing the map.
const Footer = () => {
    const { info, version } = usePage().props.nav;
    const { compact, registerFooter } = useMapLayout();
    const [open, setOpen] = useState(false);

    return (
        <>
            {compact && (
                <button type="button" className="map-controls-toggle attribution-toggle" aria-label="Map credits"
                    aria-expanded={open} aria-controls="map-credits" onClick={() => setOpen(!open)}>
                    <i className="fa-sharp fa-circle-info" aria-hidden="true"></i>
                </button>
            )}
            <footer id="map-credits" ref={registerFooter} className={`mt-auto text-white-50${open ? ' open' : ''}`}>
                <div>
                    {version}
                    {' | Created by '}<a href="https://blt950.com" target="_blank" className="text-white-50 text-decoration-underline">Blt950</a>
                    {info.map((item) => (
                        <Fragment key={item.key}>
                            {' | '}<NavItem item={item} className="text-white-50 text-decoration-underline" />
                        </Fragment>
                    ))}
                </div>
                {/* MapAttribution portals the attribution of the sources being drawn into here. */}
                <div id="map-attribution"></div>
            </footer>
        </>
    );
};

export default Footer;
