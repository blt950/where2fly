import { useRef } from 'react';
import { Head } from '@inertiajs/react';

import FlightMenu from '../components/ui/FlightMenu';
import PageTitle from '../components/ui/PageTitle';
import { useSortableRows } from '../components/utils/useSortableRows';
import { stopOnTouch } from '../components/utils/touch';
import { useTooltips } from '../components/utils/useTooltips';

const SORT_COLUMNS = {
    flight: (route) => route.flightIcao,
    airline: (route) => route.airline.iata,
    aircraft: (route) => route.aircrafts,
    lastSeen: (route) => route.lastSeen,
};

const Endpoint = ({ label, airport }) => (
    <dl>
        <dt>{label}</dt>
        <dd>
            <img className="flag" src={`/img/flags/${airport.isoCountry.toLowerCase()}.svg`} height="16"
                tabIndex={0} onClick={stopOnTouch} data-bs-toggle="tooltip" data-bs-title={airport.countryName} alt={`Flag of ${airport.countryName}`} />
            {' '}{airport.icao}
        </dd>
        <dd>{airport.name}</dd>
    </dl>
);

const SearchRoutes = ({ departure, arrival, dataSince, routes }) => {

    const contentRef = useRef(null);
    const { rows, headerProps } = useSortableRows(routes, SORT_COLUMNS);

    useTooltips(contentRef, [departure, arrival]);

    return (
        <div ref={contentRef}>
            <Head title="Results" />

            <PageTitle title="Search results" />

            <div className="container">
                <div className="results-container d-flex align-items-center">
                    <Endpoint label="Departure" airport={departure} />
                    <i className="fa-sharp fa-arrow-right fs-2"></i>
                    <Endpoint label="Arrival" airport={arrival} />
                </div>

                <div className="d-flex flex-wrap justify-content-between align-items-center">
                    <h2>Routes</h2>
                    <p className="mb-0">Data since {dataSince}</p>
                </div>

                <div className="scroll-fade">
                    <div className="table-responsive">
                        <table className="table table-hover text-start sortable asc">
                            <thead>
                                <tr>
                                    <th scope="col" width="25%" {...headerProps('flight')}>Flight</th>
                                    <th scope="col" {...headerProps('airline')}>Airline</th>
                                    <th scope="col" width="10%" {...headerProps('aircraft')}>Aircraft</th>
                                    <th scope="col" width="25%" {...headerProps('lastSeen')}>Last seen</th>
                                </tr>
                            </thead>
                            <tbody>
                                {rows.map((flight) => (
                                    <tr key={flight.id}>
                                        <td>
                                            <FlightMenu departure={departure.icao} arrival={arrival.icao}
                                                flight={{ callsign: flight.flightIcao, airlineIata: flight.airline.iata, airlineIcao: flight.airline.icao, flightNumber: flight.flightNumber }} />
                                        </td>
                                        <td>
                                            <img className="airline-logo small nopadding" src={`/img/airlines/${flight.airline.iata}.png`} alt="" />
                                            {' '}{flight.airline.name}
                                        </td>
                                        <td>{flight.aircrafts}</td>
                                        <td>{flight.lastSeen}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    );
};

SearchRoutes.mapScene = ({ airportMapData, departure, arrival }) => ({
    airports: airportMapData,
    clusterTone: 'search',
    drawRoute: [departure.icao, arrival.icao],
    camera: { type: 'fit' },
    persistView: false,
    showLists: false,
});

// Results stay as the search left the drawer: searched from a raised form, they show raised
SearchRoutes.drawer = 'keep';

export default SearchRoutes;
