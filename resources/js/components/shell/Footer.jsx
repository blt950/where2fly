import { Fragment } from 'react';
import { usePage } from '@inertiajs/react';

import NavItem from './NavItem';

// layouts/footer.blade.php is its Blade twin; the links come from NavigationHelper (`nav.info`)
const Footer = () => {
    const { info, version } = usePage().props.nav;

    return (
        <footer className="mt-auto text-white-50">
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
    );
};

export default Footer;
