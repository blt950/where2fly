import React, { useEffect, useState, useRef } from 'react';
import TAF from './TAF';
import SimbriefLink from './SimbriefLink';

function AirportCard({ airportId }) {
    const [data, setData] = useState(null);
    const [direction, setDirection] = useState(null);
    const dataRef = useRef({});

    useEffect(() => {
        if (dataRef.current[airportId]) {
            setData(dataRef.current[airportId]);
        } else {
            fetch(route('api.airport.show', airportId), { credentials: 'include', headers: { 'Accept': 'application/json' } })
                .then(response => response.json())
                .then(data => {
                    dataRef.current[airportId] = data.data;
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
                    {data.runways.map(runway => (
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

                    <a className="btn btn-outline-primary btn-sm font-work-sans" href={`https://windy.com/${data.airport.icao}`} target="_blank">
                        Windy <i className="fas fa-up-right-from-square"></i>
                    </a>

                    {!direction && (
                        <>
                            <a className="btn btn-outline-primary btn-sm font-work-sans" href={route('front', {icao: data.airport.icao})}>
                                <span>Arrival</span> <i className="fas fa-search"></i>
                            </a>
                    
                            <a className="btn btn-outline-primary btn-sm font-work-sans" href={route('front.departures', {icao: data.airport.icao})}>
                                <span>Departure</span> <i className="fas fa-search"></i>
                            </a>
                        </>
                    )}

                    <SimbriefLink 
                        className="btn btn-outline-primary btn-sm font-work-sans"
                        direction=""
                        primaryIcao={data.airport.icao}
                        secondaryIcao=""
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