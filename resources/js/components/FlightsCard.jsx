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

                    <ul className="list-unstyled">
                        {data.flights.map(flight => (
                            <li key={flight.id} className={flight.highlighted ? 'text-success' : ''}>
                                {flight.flight_icao}&nbsp;({flight.aircrafts.map(aircraft => aircraft.icao).join(',')})&nbsp;{moment(flight.last_seen_at).fromNow()}
                            </li>
                        ))}
                    </ul>
                </>
            ) : (
                <p>Loading ...</p>
            )}
            </div>
        </>
    )
}

export default FlightsCard;