import { useEffect, useState, useRef, useContext } from 'react';
import { CardContext } from './context/CardContext';
import { MapContext } from './context/MapContext';
import moment from 'moment';

function FlightsCard({ airlineId, departureAirportId, arrivalAirportId }) {

    const dataCache = useRef({});
    const [data, setData] = useState(null);
    const { airports, primaryAirport, focusAirport, highlightedAircrafts } = useContext(MapContext);
    const { setShowFlightsIdCard } = useContext(CardContext);
    
    // Fetch airport data if it's not in the cache
    useEffect(() => {
        if (dataCache.current[airlineId]) {
            setData(dataCache.current[airlineId]);
        } else {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            fetch(route('api.airport.flights'), {
                    method: "POST",    
                    credentials: 'include',
                    headers: { 
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ airlineId, departureAirportId, arrivalAirportId, highlightedAircrafts })
                })
                .then(response => response.json())
                .then(data => {
                    dataCache.current[airlineId] = data.data;
                    setData(data.data);
                })
                .catch(error => console.error(error.message));
        }
    }, [airlineId]);

    return (
        <>
            <div className="popup-card">
            {data ? (
                <>
                    <div className="d-flex justify-content-between">
                        <h2>
                            <img className="airline-logo small" alt={`${data.airline.name} logo`} src={`/img/airlines/${data.airline.iata_code}.png`}/> {data.airline.name} flights
                        </h2>

                        <button className="btn-close" aria-label="Close flights card" onClick={() => setShowFlightsIdCard(null)}></button>
                    </div>

                    <table className="table card-table no-padding sortable asc">
                        <thead>
                            <tr>
                                <th scope="col" style={{maxWidth: '25%'}}>Flight</th>
                                <th scope="col" style={{width: '50%'}}>Aircraft</th>
                                <th scope="col" style={{maxWidth: '25%'}}>Last seen</th>
                            </tr>
                        </thead>
                        <tbody>
                            {data.flights.map(flight => (
                                <tr key={flight.id}>
                                    <td className={flight.highlighted ? 'text-flight-success' : ''} data-sort={flight.flight_icao}>
                                        <div className="dropdown fs-6 text-info link-underline-info link-underline-opacity-25-hover font-work-sans ps-0">
                                            <button className="btn btn-xs btn-xs-light dropdown-toggle font-work-sans" type="button" data-bs-toggle="dropdown" aria-expanded="false">{flight.flight_icao}</button>
                                            <ul className="dropdown-menu">
                                                <li>
                                                    <a
                                                        className="dropdown-item"
                                                        href={`https://dispatch.simbrief.com/options/custom?orig=${airports[primaryAirport].icao}&dest=${airports[focusAirport].icao}&airline=${data.airline.icao_code}&fltnum=${flight.flight_number}`}
                                                        target="_blank"
                                                        rel="noreferrer"
                                                    >
                                                        Simbrief
                                                    </a>
                                                </li>
                                                <li>
                                                    <a
                                                        className="dropdown-item"
                                                        href={`https://www.flightradar24.com/data/flights/${(data.airline.iata_code + flight.flight_number).toLowerCase()}`}
                                                        target="_blank"
                                                    >
                                                        Flightradar24
                                                    </a>
                                                </li>
                                                <li>
                                                    <a
                                                        className="dropdown-item"
                                                        href={`https://www.flightaware.com/live/flight/${(data.airline.icao_code + flight.flight_number).toLowerCase()}`}
                                                        target="_blank"
                                                    >
                                                        FlightAware
                                                    </a>
                                                </li>
                                                <li>
                                                    <a
                                                        className="dropdown-item"
                                                        href={`https://www.airnavradar.com/data/flights/${(data.airline.iata_code + flight.flight_number).toLowerCase()}`}
                                                        target="_blank"
                                                    >
                                                        AirNavRadar
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                    <td className={flight.highlighted ? 'text-flight-success' : ''}>
                                        {flight.aircrafts.map(aircraft => aircraft.icao).join(', ')}
                                    </td>
                                    <td className={flight.highlighted ? 'text-flight-success' : ''} data-sort={flight.last_seen_at}>
                                        {moment(flight.last_seen_at).fromNow()}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </>
            ) : (
                <p className="mb-0"><i className="fa-sharp fa-spinner-third fa-spin"></i>&nbsp;&nbsp;Loading</p>
            )}
            </div>
        </>
    );
}

export default FlightsCard;