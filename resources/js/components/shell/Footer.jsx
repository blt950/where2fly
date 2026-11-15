import { usePage } from '@inertiajs/react';

const Footer = () => {
    const { appVersion } = usePage().props;

    return (
        <footer className="mt-auto text-white-50">
            <div>
                {appVersion}
                {' | Created by '}<a href="https://blt950.com" target="_blank" className="text-white-50 text-decoration-underline">Blt950</a>
                {' | '}<a href={route('changelog')} className="text-white-50 text-decoration-underline">Changelog</a>
                {' | '}<a href={route('privacy')} className="text-white-50 text-decoration-underline">Privacy Policy</a>
                {' | '}<a href={route('api')} className="text-white-50 text-decoration-underline">API</a>
            </div>
            {/* MapAttribution portals the attribution of the sources being drawn into here. */}
            <div id="map-attribution"></div>
        </footer>
    );
};

export default Footer;
