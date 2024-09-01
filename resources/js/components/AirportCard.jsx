import { useEffect, useState, useRef, useContext } from 'react';
import { MapContext } from './context/MapContext';

import SimbriefLink from './ui/SimbriefLink';
import TAF from './ui/TAF';

function AirportCard({ airportId }) {
    const [data, setData] = useState(null);
    const dataCache = useRef({});
    const { primaryAirport, focusAirport, reverseDirection } = useContext(MapContext);

    // Fetch airport data if it's not in the cache
    useEffect(() => {
        if (dataCache.current[airportId]) {
            setData(dataCache.current[airportId]);
        } else {
            fetch(route('api.airport.show', airportId), { credentials: 'include', headers: { 'Accept': 'application/json' } })
                .then(response => response.json())
                .then(data => {
                    dataCache.current[airportId] = data.data;
                    setData(data.data);
                })
                .catch(error => console.error(error.message));
        }
    }, [airportId]);

    return (
        <div className="popup-card show">
            {data ? (
                <>
                    <div>
                        <img className="flag border-0" src={`/img/flags/${ data.airport.iso_country.toLowerCase() }.svg`} height="16" data-bs-toggle="tooltip" data-bs-title={ data.airport.country_name } alt={`Flag of ${data.airport.country_name}`}></img>
                        &nbsp;{data.airport.icao}
                    </div>
                    <h2>{data.airport.name}</h2>

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
                    </dl>

                    <div className="d-flex flex-wrap gap-2">
                        <button className="btn btn-outline-primary btn-sm font-work-sans">
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
                <p>Loading ...</p>
            )}
        </div>
    );
}

export default AirportCard;