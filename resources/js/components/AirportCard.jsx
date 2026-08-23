import { useEffect, useState, useRef, useContext } from 'react';
import { captureException } from '@sentry/react';
import { MapContext } from './context/MapContext';
import { CardContext } from './context/CardContext';

import FlightsCard from './FlightsCard';
import SceneryCard from './SceneryCard';
import SimbriefLink from './ui/SimbriefLink';
import TAF from './ui/TAF';

import ExternalLinkTracker from './utils/ExternalLinkTracker';
import TooltipRefresh from './utils/TooltipRefresh';

function AirportCard({ airportId }) {
    const dataCache = useRef({});
    const [data, setData] = useState(null);
    const [showFlightsIdCard, setShowFlightsIdCard] = useState(null);
    const [showSceneryIdCard, setShowSceneryIdCard] = useState(null);
    const [departureAirportId, setDepartureAirportId] = useState(null);
    const [arrivalAirportId, setArrivalAirportId] = useState(null);
    const { findAirport, primaryAirport, focusAirport, reverseDirection, highlightedAircrafts, setFocusAirport, setShowAirportIdCard } = useContext(MapContext);

    // Closing unmounts the card and its flights/scenery children; focusAirport
    // is cleared too so clicking the same marker again reopens the card
    const closeCard = () => {
        setShowAirportIdCard(null);
        setFocusAirport(null);
    };

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
                        primaryAirport: (primaryAirport ? findAirport(primaryAirport).id : null),
                        secondaryAirport: findAirport(focusAirport).id, 
                        reverseDirection,
                        highlightedAircrafts
                    })
                })
                .then(response => response.json())
                .then(data => {
                    dataCache.current[airportId] = data.data;
                    setData(data.data);
                })
                .catch(error => {
                    captureException(error);
                    console.error(error.message);
                });
        }

        setShowFlightsIdCard(null);
        setShowSceneryIdCard(null);

        if(reverseDirection === false){
            setDepartureAirportId(findAirport(primaryAirport).id);
            setArrivalAirportId(findAirport(focusAirport).id);
        } else if (reverseDirection === true) {
            setDepartureAirportId(findAirport(focusAirport).id);
            setArrivalAirportId(findAirport(primaryAirport).id);
        }

        // Dispatch a custom event when the map focuses on an airport
        window.dispatchEvent(new CustomEvent('airportReady', { detail: { icao: findAirport(focusAirport).icao } }));

    }, [airportId]);

    useEffect(() => { if(showFlightsIdCard !== null) {
        if(window.umami){
            umami.track('Interactions', {interaction: `Open flights card`})
        }
    }}, [showFlightsIdCard]);
    useEffect(() => {if(showSceneryIdCard !== null) {
        if(window.umami){
            umami.track('Interactions', {interaction: `Open scenery card`})
        }
    }}, [showSceneryIdCard]);

    // When data changes, initialize tooltips
    useEffect(() => {
        ExternalLinkTracker();
        TooltipRefresh();
    }, [data]);

    return (
        <CardContext.Provider value={{ showFlightsIdCard, setShowFlightsIdCard, setShowSceneryIdCard }}>
            <div className="popup-card">
                {data ? (
                    <>
                        <div className="d-flex justify-content-between">
                            <div>
                                <img
                                    className="flag border-0"
                                    src={`/img/flags/${ data.airport.iso_country.toLowerCase() }.svg`}
                                    height="16"
                                    data-bs-toggle="tooltip"
                                    data-bs-title={ data.airport.country }
                                    alt={`Flag of ${data.airport.country}`}
                                />
                                &nbsp;{data.airport.icao}
                            </div>
                            <button className="btn-close" aria-label="Close airport card" onClick={closeCard}></button>
                        </div>
                        <h2>{data.airport.name}</h2>

                        {data.lists.map(list => (
                            <span className="badge me-1" style={{ border: '1px solid ' + list.color, color: list.color }} key={list.id}><i className="fa-sharp fa-list"></i>&nbsp;{list.name}</span>
                        ))}

                        {data.notable && (
                            <div className="notable">
                                <h3>Notable airport</h3>
                                <div className="d-flex flex-row flex-wrap">
                                    {data.notable.tags.map(tag => (
                                        <span className="badge me-1" key={tag.name}><i className={`fa-sharp fa-regular ${tag.icon}`}></i>&nbsp;&nbsp;{tag.name}</span>
                                    ))}
                                </div>
                                <p>{data.notable.description}</p>
                                <a href={data.notable.source} target="_blank">
                                    <i className="fa-sharp fa-link-simple"></i> {data.notable.source_tld}
                                </a>
                            </div>
                        )}

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
                                <TAF taf={data.taf}/>
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
                            <button className="btn btn-outline-primary btn-sm" onClick={() => setShowSceneryIdCard(data.airport.icao)}>
                                <i className="fa-sharp fa-map"></i> Scenery
                            </button>

                            {(primaryAirport === undefined || primaryAirport === null) && (
                                <>
                                    <a className="btn btn-outline-primary btn-sm" href={route('front', {icao: data.airport.icao})}>
                                    <i className="fa-sharp fa-search"></i> <span>Arrival</span>
                                    </a>
                            
                                    <a className="btn btn-outline-primary btn-sm" href={route('front.departures', {icao: data.airport.icao})}>
                                        <i className="fa-sharp fa-search"></i> <span>Departure</span>
                                    </a>
                                </>
                            )}

                            {(primaryAirport !== undefined && primaryAirport !== null) && (
                                <>
                                    <button className="btn btn-outline-primary btn-sm" onClick={() => {
                                        const params = new URLSearchParams(window.location.search);
                                        params.set('icao', data.airport.icao);
                                        window.location.href = `${window.location.pathname}?${params.toString()}`;

                                        if(window.umami){
                                            umami.track('Interactions', {interaction: `Change airport from card`})
                                        }
                                    }}>
                                        <i className="fa-sharp fa-pencil"></i> Use as {reverseDirection ? 'Arrival' : 'Departure'}
                                    </button>
                                </>    
                            )}

                            <a className="btn btn-outline-light btn-sm" href={`https://windy.com/${data.airport.icao}?utm_campaign=where2fly.today`} target="_blank">
                                Windy <i className="fa-sharp fa-up-right-from-square"></i>
                            </a>

                            <a className="btn btn-outline-light btn-sm" href={`https://charts.navigraph.com/airport/${data.airport.icao}?chartCategory=APP&informationSection=General&section=Charts&utm_campaign=where2fly.today`} target="_blank">
                                Navigraph <i className="fa-sharp fa-up-right-from-square"></i>
                            </a>

                            <SimbriefLink 
                                className="btn btn-outline-primary btn-sm"
                                direction={reverseDirection}
                                primaryIcao={primaryAirport}
                                secondaryIcao={focusAirport}
                            />
                        </div>
                    </>
                ) : (
                    <p className="mb-0"><i className="fa-sharp fa-spinner-third fa-spin"></i>&nbsp;&nbsp;Loading</p>
                )}
            </div>
            {showFlightsIdCard && <FlightsCard airlineId={showFlightsIdCard} departureAirportId={departureAirportId} arrivalAirportId={arrivalAirportId} reverseDirection={reverseDirection} />}
            {showSceneryIdCard && <SceneryCard airportId={showSceneryIdCard} />}
        </CardContext.Provider>
    );
}

export default AirportCard;