import { useState } from 'react';
import { Head, Link } from '@inertiajs/react';

import { useShellMap } from '../components/shell/ShellMapContext';
import PageTitle from '../components/ui/PageTitle';

const Scenery = ({ simulators, filteredSimulator }) => {

    const { setFocusAirport } = useShellMap();
    const [icao, setIcao] = useState('');

    // An unknown ICAO is reported by the shell, in the alerts above
    const search = (event) => {
        event.preventDefault();
        setFocusAirport(icao);
    };

    const filterClass = (active) => `btn btn-sm ${active ? 'btn-primary' : 'btn-outline-primary'}`;

    return (
        <>
            <Head title="Scenery" />

            <PageTitle title="Scenery" subtitle="Find scenery for your flight simulator" />

            <div className="container">

                <div>
                    <div className="mb-4">
                        <div className="filterbox">
                            <span className="m-0"><strong className="d-block">Map Filter</strong>
                                <Link className={filterClass(!filteredSimulator)} href={route('scenery')} preserveScroll>All</Link>
                                {simulators.map((simulator) => (
                                    <Link key={simulator} className={filterClass(filteredSimulator === simulator)}
                                        href={route('scenery.filtered', simulator)} preserveScroll>
                                        {simulator}
                                    </Link>
                                ))}
                            </span>
                        </div>
                    </div>

                    <form onSubmit={search}>
                        <label htmlFor="airport">Search for airport (ICAO)</label>
                        <div className="input-group">
                            <input type="text" className="form-control" id="airport" name="airport" required maxLength="4"
                                value={icao} onChange={(event) => setIcao(event.target.value.toUpperCase())} />
                            <button className="btn btn-primary ms-2" type="submit">Search</button>
                        </div>
                    </form>
                </div>
            </div>
        </>
    );
};

// The browse view: the user's own position is kept and restored, and every focused airport opens
// its scenery card straight away.
Scenery.mapScene = ({ airportMapData }) => ({
    airports: airportMapData,
    clusterTone: 'search',
    camera: { type: 'saved' },
    persistView: true,
    showLists: false,
    initialCard: 'scenery',
});

export default Scenery;
