import { useEffect, useState, useRef, useContext } from 'react';
import { captureException } from '@sentry/react';
import { MapContext } from './context/MapContext';

import SimbriefLink from './ui/SimbriefLink';
import TAF from './ui/TAF';

import { useTooltips } from './utils/useTooltips';

// The airport itself. The flights and scenery cards beside it belong to CardHost, which opens
// them through `onOpenFlights` / `onOpenScenery`. `inTab`: shown as a drawer tab, see CardHost.
function AirportCard({ airportId, onOpenFlights, onOpenScenery, onClose, inTab = false }) {
    const dataCache = useRef({});
    const cardRef = useRef(null);
    const [data, setData] = useState(null);
    const { findAirport, primaryAirport, focusAirport, reverseDirection, highlightedAircrafts, navigate } = useContext(MapContext);

    // A plain click navigates through the map's owner (keeping the map in the shell); a modified
    // click (new tab, new window) is left to the browser
    const followLink = (event) => {
        if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

        event.preventDefault();
        navigate(event.currentTarget.href);
    };

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

        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [airportId]);

    // Tooltips for this card only, disposed with it
    useTooltips(cardRef, [data]);

    return (
        <div className="popup-card" ref={cardRef}>
            {data ? (
                <>
                    <div className="d-flex justify-content-between">
                        <div>
                            <img
                                className="flag border-0"
                                src={`/img/flags/${ data.airport.iso_country.toLowerCase() }.svg`}
                                height="16"
                                tabIndex={0}
                                data-bs-toggle="tooltip"
                                data-bs-title={ data.airport.country }
                                alt={`Flag of ${data.airport.country}`}
                            />
                            &nbsp;{data.airport.icao}
                        </div>
                        {!inTab && <button className="btn-close" aria-label="Close airport card" onClick={onClose}></button>}
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

                        {/* In a tab, the Flights tab replaces these shortcuts */}
                        {!inTab && data.airlines && data.airlines.length > 0 && (
                            <>
                                <dt>Flights</dt>
                                <dd className="d-flex flex-wrap gap-1">
                                    {data.airlines.map(airline => (
                                        <button
                                            key={airline.id}
                                            type="button"
                                            className={`airline-button ${airline.highlighted ? 'highlight' : 'mb-1'}`}
                                            onClick={() => onOpenFlights(airline.icao_code)}
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
                        {/* In a tab, the Scenery tab replaces this button */}
                        {!inTab && (
                            <button className="btn btn-outline-primary btn-sm" onClick={onOpenScenery}>
                                <i className="fa-sharp fa-map"></i> Scenery
                            </button>
                        )}

                        {(primaryAirport === undefined || primaryAirport === null) && (
                            <>
                                <a className="btn btn-outline-primary btn-sm" href={route('front', {icao: data.airport.icao})} onClick={followLink}>
                                    <i className="fa-sharp fa-search"></i> <span>Arrival</span>
                                </a>

                                <a className="btn btn-outline-primary btn-sm" href={route('front.departures', {icao: data.airport.icao})} onClick={followLink}>
                                    <i className="fa-sharp fa-search"></i> <span>Departure</span>
                                </a>
                            </>
                        )}

                        {(primaryAirport !== undefined && primaryAirport !== null) && (
                            <>
                                <button className="btn btn-outline-primary btn-sm" onClick={() => {
                                    const params = new URLSearchParams(window.location.search);
                                    params.set('icao', data.airport.icao);
                                    navigate(`${window.location.pathname}?${params.toString()}`);

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
    );
}

export default AirportCard;