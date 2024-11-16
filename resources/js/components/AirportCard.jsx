import { useEffect, useState, useRef, useContext } from 'react';
import { MapContext } from './context/MapContext';
import { CardContext } from './context/CardContext';

import FlightsCard from './FlightsCard';
import SceneryCard from './SceneryCard';
import SimbriefLink from './ui/SimbriefLink';
import TAF from './ui/TAF';

import ExternalLinkTracker from './utils/ExternalLinkTracker';

function AirportCard({ airportId }) {
    const dataCache = useRef({});
    const [data, setData] = useState(null);
    const [showFlightsIdCard, setShowFlightsIdCard] = useState(null);
    const [showSceneryIdCard, setShowSceneryIdCard] = useState(null);
    const [departureAirportId, setDepartureAirportId] = useState(null);
    const [arrivalAirportId, setArrivalAirportId] = useState(null);
    const { airports, primaryAirport, focusAirport, reverseDirection, highlightedAircrafts } = useContext(MapContext);

    useEffect(() => {
        window.setShowSceneryIdCard = (data) => { setShowSceneryIdCard(data) }
    }), [];

    // Fetch airport data if it's not in the cache
    useEffect(() => {
        if (dataCache.current[airportId]) {
            setData(dataCache.current[airportId]);
        } else {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            fetch(route('api.airport.show'), {
                    method: "POST",    
                    credentials: 'include',
                    headers: { 
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ 
                        primaryAirport: (primaryAirport ? airports[primaryAirport].id : null),
                        secondaryAirport: airports[focusAirport].id, 
                        reverseDirection,
                        highlightedAircrafts
                    })
                })
                .then(response => response.json())
                .then(data => {
                    dataCache.current[airportId] = data.data;
                    setData(data.data);
                })
                .catch(error => console.error(error.message));
        }

        setShowFlightsIdCard(null);
        setShowSceneryIdCard(null);

        if(reverseDirection === false){
            setDepartureAirportId(airports[primaryAirport].id);
            setArrivalAirportId(airports[focusAirport].id);
        } else if (reverseDirection === true) {
            setDepartureAirportId(airports[focusAirport].id);
            setArrivalAirportId(airports[primaryAirport].id);
        }

        // Dispatch a custom event when the map focuses on an airport
        window.dispatchEvent(new CustomEvent('airportReady', { detail: { icao: airports[focusAirport].icao } }));

    }, [airportId]);

    useEffect(() => { if(showFlightsIdCard !== null) {
        plausible('Interactions', {props: {interaction: `Open flights card`}})
        umami.track('Interactions', {interaction: `Open flights card`})
    }}, [showFlightsIdCard]);
    useEffect(() => {if(showSceneryIdCard !== null) {
        plausible('Interactions', {props: {interaction: `Open scenery card`}})
        umami.track('Interactions', {interaction: `Open scenery card`})
    }}, [showSceneryIdCard]);

    // When data changes, initialize tooltips
    useEffect(() => {
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl, {
            container: 'body'
        }));

        ExternalLinkTracker();

    }, [data]);

    return (
        <CardContext.Provider value={{ showFlightsIdCard, setShowFlightsIdCard, setShowSceneryIdCard }}>
            <div className="popup-card">
                {data ? (
                    <>
                        <div>
                            <img 
                                className="flag border-0" 
                                src={`/img/flags/${ data.airport.iso_country.toLowerCase() }.svg`} 
                                height="16" 
                                data-bs-toggle="tooltip" 
                                data-bs-title={ data.airport.country_name } 
                                alt={`Flag of ${data.airport.country_name}`}
                            />
                            &nbsp;{data.airport.icao}
                        </div>
                        <h2>{data.airport.name}</h2>

                        {data.lists.map(list => (
                            <span className="badge me-1" style={{ border: '1px solid ' + list.color, color: list.color }} key={list.id}><i className="fas fa-list"></i>&nbsp;{list.name}</span>
                        ))}

                        <dl className="font-kanit">
                            <dt>Runways</dt>
                            {data.airport.runways.map(runway => (
                                <dd key={runway.id}>
                                    <strong>{runway.le_ident}/{runway.he_ident}:</strong>
                                    &nbsp;{runway.length_ft.toLocaleString('en-US')}ft <span className="text-white-50">({Math.round(runway.length_ft * .3048, 0).toLocaleString('en-US')}m)</span>
                                </dd>
                            ))}

                            <dt>METAR</dt>
                            <dd>{data.metar ? data.metar : 'Not Available'}</dd>

                            <dt>TAF</dt>
                            <dd>
                                <TAF icao={data.airport.icao}/>
                            </dd>

                            {data.airlines && data.airlines.length > 0 && (
                                <>
                                    <dt>Flights</dt>
                                    <dd className="d-flex flex-wrap gap-1">
                                        {data.airlines.map(airline => (
                                            <button
                                                key={airline.id}
                                                type="button"
                                                className={`airline-button ${airline.highlighted ? 'highlight' : 'mb-1'}`}
                                                onClick={() => setShowFlightsIdCard(airline.icao_code)}
                                            >
                                                <img
                                                    data-bs-toggle="tooltip"
                                                    data-bs-title={`See all ${airline.name} flights`}
                                                    className="airline-logo button"
                                                    src={`/img/airlines/${airline.iata_code}.png`}
                                                    alt={`See all ${airline.name} flights`}
                                                />
                                            </button>
                                        ))}
                                    </dd>
                                </>
                            )}
                        </dl>

                        <div className="d-flex flex-wrap gap-2">
                            <button className="btn btn-outline-primary btn-sm font-work-sans" onClick={() => setShowSceneryIdCard(data.airport.icao)}>
                                <i className="fas fa-map"></i> Scenery
                            </button>

                            {(primaryAirport === undefined || primaryAirport === null) && (
                                <>
                                    <a className="btn btn-outline-primary btn-sm font-work-sans" href={route('front', {icao: data.airport.icao})}>
                                    <i className="fas fa-search"></i> <span>Arrival</span>
                                    </a>
                            
                                    <a className="btn btn-outline-primary btn-sm font-work-sans" href={route('front.departures', {icao: data.airport.icao})}>
                                        <i className="fas fa-search"></i> <span>Departure</span>
                                    </a>
                                </>
                            )}

                            <a className="btn btn-outline-light btn-sm font-work-sans" href={`https://windy.com/${data.airport.icao}`} target="_blank">
                                Windy <i className="fas fa-up-right-from-square"></i>
                            </a>

                            <SimbriefLink 
                                className="btn btn-outline-primary btn-sm font-work-sans"
                                direction={reverseDirection}
                                primaryIcao={primaryAirport}
                                secondaryIcao={focusAirport}
                            />
                        </div>
                    </>
                ) : (
                    <p className="mb-0"><i className="fas fa-spinner-third fa-spin"></i>&nbsp;&nbsp;Loading</p>
                )}
            </div>
            {showFlightsIdCard && <FlightsCard airlineId={showFlightsIdCard} departureAirportId={departureAirportId} arrivalAirportId={arrivalAirportId} />}
            {showSceneryIdCard && <SceneryCard airportId={showSceneryIdCard} />}
        </CardContext.Provider>
    );
}

export default AirportCard;