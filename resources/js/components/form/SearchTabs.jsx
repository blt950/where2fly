import { Link } from '@inertiajs/react';

const TABS = [
    { name: 'front', icon: 'fa-plane-arrival', label: 'Find Arrival' },
    { name: 'front.departures', icon: 'fa-plane-departure', label: 'Find Departure' },
    { name: 'front.routes', icon: 'fa-route', label: 'Find Flights' },
];

const SearchTabs = () => (
    <div className="btn-group filter" style={{ textAlign: 'left', paddingBottom: '2rem' }}>
        {TABS.map((tab) => (
            <Link key={tab.name} href={route(tab.name)} className={`btn btn-outline-primary${route().current(tab.name) ? ' active' : ''}`}>
                <i className={`fa-sharp ${tab.icon}`}></i> {tab.label}
            </Link>
        ))}
    </div>
);

export default SearchTabs;
