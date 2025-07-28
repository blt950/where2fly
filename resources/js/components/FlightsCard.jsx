import { useEffect, useState, useRef, useContext } from 'react';
import { CardContext } from './context/CardContext';
import { MapContext } from './context/MapContext';
import moment from 'moment';

function FlightsCard({ airlineId, departureAirportId, arrivalAirportId }) {

    const dataCache = useRef({});
    const [data, setData] = useState(null);
    const { highlightedAircrafts } = useContext(MapContext);
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
                                <th scope="col" style={{'maxWidth': '25%'}}>Flight</th>
                                <th scope="col" style={{'width': '50%'}}>Aircraft</th>
                                <th scope="col" style={{'maxWidth': '25%'}}>Last seen</th>
                            </tr>
                        </thead>
                        <tbody>
                            {data.flights.map(flight => (
                                <tr key={flight.id}>
                                    <td className={flight.highlighted ? 'text-flight-success' : ''} data-sort={flight.flight_icao}>{flight.flight_icao}</td>
                                    <td className={flight.highlighted ? 'text-flight-success' : ''}>{flight.aircrafts.map(aircraft => aircraft.icao).join(', ')}</td>
                                    <td className={flight.highlighted ? 'text-flight-success' : ''} data-sort={flight.last_seen_at}>{moment(flight.last_seen_at).fromNow()}</td>
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
    )
}

export default FlightsCard;