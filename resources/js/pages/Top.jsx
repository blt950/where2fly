import { useEffect, useRef } from 'react';
import { Head, Link } from '@inertiajs/react';

import { useShellMap } from '../components/shell/ShellMapContext';
import { CONTINENT_VIEWS, WORLD_VIEW } from '../components/map/mapScene';
import Dropdown from '../components/ui/Dropdown';
import Legend from '../components/ui/Legend';
import PageTitle from '../components/ui/PageTitle';
import ScoreIcon from '../components/ui/ScoreIcon';
import { useSortableRows } from '../components/utils/useSortableRows';
import { stopOnTouch } from '../components/utils/touch';
import { useTooltips } from '../components/utils/useTooltips';

// Each dropdown links to a URL keeping the other two filters intact. JM is the default aircraft,
// so "no filter" must travel as an explicit aircraft=all rather than an absent param.
const topRoute = (params) => {
    const present = Object.fromEntries(Object.entries(params).filter(([, value]) => value));

    return route(present.continent ? 'top.filtered' : 'top', present);
};

const SORT_COLUMNS = {
    rank: (airport) => airport.rank,
    airport: (airport) => airport.icao,
    forecast: (airport) => airport.scores.length,
};

const FilterDropdown = ({ label, children }) => (
    <Dropdown label={label} toggleClassName="btn btn-sm btn-outline-primary">{children}</Dropdown>
);

const FilterItem = ({ active, href, children }) => (
    <li>
        <Link className={`dropdown-item${active ? ' active' : ''}`} href={href} preserveScroll prefetch>{children}</Link>
    </li>
);

const Top = ({ airports, continent, exclude, aircraft, continents, aircraftTypes, scoreTypes }) => {

    const { focusAirport, setFocusAirport, pingAirport } = useShellMap();
    const tableRef = useRef(null);
    const rowRefs = useRef({});
    const { rows, headerProps } = useSortableRows(airports, SORT_COLUMNS);
    const aircraftParam = aircraft ?? 'all';

    useTooltips(tableRef, [airports]);

    // Focus can come from a click on the map: bring that row into view, unless it already is.
    useEffect(() => {
        const row = rowRefs.current[focusAirport];
        const rect = row?.getBoundingClientRect();

        if (rect && (rect.top < 0 || rect.bottom > window.innerHeight)) {
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }, [focusAirport]);

    const focus = (icao) => {
        setFocusAirport(icao);
        pingAirport(icao);
    };

    return (
        <>
            <Head title="Top List" />

            <PageTitle title="Top Airports Right Now" subtitle="Destinations with the worst weather right now" />

            <div className="container">
                <div className="d-flex flex-wrap gap-2 mb-3">
                    <FilterDropdown label={`Continent: ${continent ? continents[continent] : 'All'}`}>
                        <FilterItem active={continent === null} href={topRoute({ exclude, aircraft: aircraftParam })}>All</FilterItem>
                        {Object.entries(continents).map(([code, name]) => (
                            <FilterItem key={code} active={continent === code} href={topRoute({ continent: code, exclude, aircraft: aircraftParam })}>
                                {name}
                            </FilterItem>
                        ))}
                    </FilterDropdown>

                    <FilterDropdown label={`Aircraft Type: ${aircraft ? aircraftTypes[aircraft].name : 'All'}`}>
                        <FilterItem active={aircraft === null} href={topRoute({ continent, exclude, aircraft: 'all' })}>All</FilterItem>
                        {Object.entries(aircraftTypes).map(([code, type]) => (
                            <FilterItem key={code} active={aircraft === code} href={topRoute({ continent, exclude, aircraft: code })}>
                                {type.name}
                            </FilterItem>
                        ))}
                    </FilterDropdown>

                    <FilterDropdown label={`VATSIM: ${exclude === 'vatsim' ? 'Excluded' : 'Included'}`}>
                        <FilterItem active={!exclude} href={topRoute({ continent, aircraft: aircraftParam })}>Included</FilterItem>
                        <FilterItem active={exclude === 'vatsim'} href={topRoute({ continent, exclude: 'vatsim', aircraft: aircraftParam })}>Excluded</FilterItem>
                    </FilterDropdown>
                </div>

                <div className="table-responsive" ref={tableRef}>
                    <table className="table table-hover text-start sortable asc mb-0">
                        <thead>
                            <tr>
                                <th scope="col" {...headerProps('rank')}>#</th>
                                <th scope="col" {...headerProps('airport')}>Airport</th>
                                <th scope="col" width="10%" {...headerProps('forecast')}>Forecast</th>
                            </tr>
                        </thead>
                        <tbody>
                            {rows.map((airport) => (
                                <tr key={airport.icao} ref={(row) => { rowRefs.current[airport.icao] = row; }}
                                    className={`pointer${focusAirport === airport.icao ? ' active' : ''}`}
                                    onClick={() => focus(airport.icao)}>
                                    <th scope="row">{airport.rank}</th>
                                    <td>
                                        <div>
                                            <img className="flag" src={`/img/flags/${airport.isoCountry.toLowerCase()}.svg`} height="16"
                                                tabIndex={0} onClick={stopOnTouch} data-bs-toggle="tooltip" data-bs-title={airport.countryName} alt={`Flag of ${airport.countryName}`} />
                                            {' '}{airport.icao}
                                        </div>
                                        {airport.name}
                                    </td>
                                    <td className="fs-5">
                                        {airport.scores.map((score) => <ScoreIcon key={score.reason} score={score} />)}
                                    </td>
                                </tr>
                            ))}
                            {airports.length === 0 && (
                                <tr>
                                    <th colSpan="9" className="text-center text-warning">
                                        <i className="fa-sharp fa-exclamation-triangle"></i> No top airports available. Weather database could be updating, please try again in a few minutes.
                                    </th>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <Legend scoreTypes={scoreTypes} />
            </div>
        </>
    );
};

Top.mapScene = ({ airportMapData, continent }) => ({
    airports: airportMapData,
    clusterTone: 'search',
    // On a phone a continent's view (tuned for a wide map) gives way to framing its airports in
    // what the drawer leaves; the whole-world list stays on the world view, a fit would be zoom 0
    camera: { type: 'view', view: CONTINENT_VIEWS[continent] ?? WORLD_VIEW, ...(continent ? { compact: 'fit' } : {}) },
    persistView: false,
    showLists: false,
});

// Hidden on /top before the shell; /top/{continent} showing it was an oversight in the route check.
Top.showMapHint = false;

export default Top;
