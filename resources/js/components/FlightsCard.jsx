import { useContext } from 'react';
import { MapContext } from './context/MapContext';
import fromNow from './utils/RelativeTime';
import { useFlights } from './utils/useFlights';

const flightLinks = (flight, departure, arrival) => {
    const { airline } = flight;
    const iataFlight = `${airline.iata_code}${flight.flight_number}`.toLowerCase();
    const icaoFlight = `${airline.icao_code}${flight.flight_number}`.toLowerCase();

    return [
        ['Simbrief', `https://dispatch.simbrief.com/options/custom?orig=${departure}&dest=${arrival}&airline=${airline.icao_code}&fltnum=${flight.flight_number}&utm_campaign=where2fly.today`],
        ['Flightradar24', `https://www.flightradar24.com/data/flights/${iataFlight}?utm_campaign=where2fly.today`],
        ['FlightAware', `https://www.flightaware.com/live/flight/${icaoFlight}?utm_campaign=where2fly.today`],
        ['AirNavRadar', `https://www.airnavradar.com/data/flights/${iataFlight}?utm_campaign=where2fly.today`],
    ];
};

// Every flight on the route, narrowed by an airline filter. One fetch per route; switching airline
// only filters, so the card (and a phone tab) keeps a single, stable destination.
function FlightsCard({ departureAirportId, arrivalAirportId, airline, onAirlineChange, onClose, inTab = false }) {

    const { findAirport, primaryAirport, focusAirport, reverseDirection, highlightedAircrafts } = useContext(MapContext);
    const data = useFlights(departureAirportId, arrivalAirportId, highlightedAircrafts);

    const selected = data?.airlines.find((candidate) => candidate.icao_code === airline);
    const soleAirline = data?.airlines.length === 1 ? data.airlines[0] : null;
    const flights = data ? data.flights.filter((flight) => !airline || flight.airline.icao_code === airline) : [];
    const departure = findAirport(reverseDirection ? focusAirport : primaryAirport)?.icao;
    const arrival = findAirport(reverseDirection ? primaryAirport : focusAirport)?.icao;

    return (
        <div className="popup-card">
            {data ? (
                <>
                    <div className="d-flex justify-content-between">
                        <h2>
                            {selected && <><img className="airline-logo small" alt={`${selected.name} logo`} src={`/img/airlines/${selected.iata_code}.png`} />{' '}</>}
                            {selected ? `${selected.name} flights` : 'Flights'}
                        </h2>

                        {!inTab && <button className="btn-close" aria-label="Close flights card" onClick={onClose}></button>}
                    </div>

                    {data.airlines.length > 1 && (
                        <div className="flights-filter d-flex flex-wrap align-items-center gap-1 mb-2" role="group" aria-label="Filter by airline">
                            <button type="button" className={`btn btn-sm ${airline ? 'btn-outline-primary' : 'btn-primary'}`}
                                aria-pressed={!airline} onClick={() => onAirlineChange(null)}>All</button>
                            {data.airlines.map((candidate) => (
                                <button key={candidate.icao_code} type="button" className="airline-button" title={candidate.name}
                                    aria-label={`Only ${candidate.name}`} aria-pressed={airline === candidate.icao_code}
                                    onClick={() => onAirlineChange(airline === candidate.icao_code ? null : candidate.icao_code)}>
                                    <img className={`airline-logo small${airline === candidate.icao_code ? ' button' : ''}`} alt=""
                                        src={`/img/airlines/${candidate.iata_code}.png`} />
                                </button>
                            ))}
                        </div>
                    )}

                    <table className="table card-table no-padding">
                        <thead>
                            <tr>
                                <th scope="col" style={{ maxWidth: '25%' }}>Flight</th>
                                <th scope="col" style={{ width: '50%' }}>Aircraft</th>
                                <th scope="col" style={{ maxWidth: '25%' }}>Last seen</th>
                            </tr>
                        </thead>
                        <tbody>
                            {flights.map((flight) => {
                                const tone = flight.highlighted ? 'text-flight-success' : '';

                                return (
                                    <tr key={flight.id}>
                                        <td className={tone}>
                                            <div className="dropdown fs-6 text-info link-underline-info link-underline-opacity-25-hover ps-0">
                                                {/* With one airline there's no filter row to say whose flights these are */}
                                                {soleAirline && (
                                                    <img className="airline-logo small me-1" alt={`${soleAirline.name} logo`} src={`/img/airlines/${soleAirline.iata_code}.png`} />
                                                )}
                                                <button className="btn btn-xs btn-xs-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">{flight.flight_icao}</button>
                                                <ul className="dropdown-menu">
                                                    {flightLinks(flight, departure, arrival).map(([label, href]) => (
                                                        <li key={label}><a className="dropdown-item" href={href} target="_blank" rel="noreferrer">{label}</a></li>
                                                    ))}
                                                </ul>
                                            </div>
                                        </td>
                                        <td className={tone}>{flight.aircrafts.map((aircraft) => aircraft.icao).join(', ')}</td>
                                        <td className={tone}>{fromNow(flight.last_seen_at)}</td>
                                    </tr>
                                );
                            })}
                            {flights.length === 0 && (
                                <tr><td colSpan="3" className="text-white-50">No recent flights on this route</td></tr>
                            )}
                        </tbody>
                    </table>
                </>
            ) : (
                <p className="mb-0"><i className="fa-sharp fa-spinner-third fa-spin"></i>&nbsp;&nbsp;Loading</p>
            )}
        </div>
    );
}

export default FlightsCard;
