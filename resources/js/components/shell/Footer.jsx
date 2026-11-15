import { Link, usePage } from '@inertiajs/react';

const Footer = () => {
    const { appVersion } = usePage().props;

    return (
        <footer className="mt-auto text-white-50">
            <div>
                {appVersion}
                {' | Created by '}<a href="https://blt950.com" target="_blank" className="text-white-50 text-decoration-underline">Blt950</a>
                {' | '}<Link href={route('changelog')} className="text-white-50 text-decoration-underline">Changelog</Link>
                {' | '}<Link href={route('privacy')} className="text-white-50 text-decoration-underline">Privacy Policy</Link>
                {' | '}<Link href={route('api')} className="text-white-50 text-decoration-underline">API</Link>
            </div>
            {/* MapAttribution portals the attribution of the sources being drawn into here. */}
            <div id="map-attribution"></div>
        </footer>
    );
};

export default Footer;
