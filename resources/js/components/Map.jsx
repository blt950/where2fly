import { DivIcon } from 'leaflet';
import React, { useState, useEffect, useRef } from 'react';
import ReactDOM from 'react-dom/client';
import { MapContainer, TileLayer, Marker, Tooltip } from 'react-leaflet'
import PopupContainer from './PopupContainer';
import MarkerClusterGroup from 'react-leaflet-cluster';
import PanEvent from './PanEvent';
import SaveViewEvent from './SaveViewEvent';

const createIcon = (color, airportType = 'large_airport') => {
    let sizePx = 10;
    if(color === null){ color = '#ddb81c'; }

    if (airportType === 'medium_airport') {
        sizePx = 7;
    } else if (airportType === 'small_airport') {
        sizePx = 5;
    }

    return new DivIcon({
        iconSize: [sizePx, sizePx],
        html: `<span style="display: block; width: ${sizePx}px; height: ${sizePx}px; border-radius: 50%; background: ${color}"></span>`
    });
};

const getMapPosition = () => {
    var storedPosition = localStorage.getItem('mapPosition');
    if (storedPosition) {
        const { lat, lng } = JSON.parse(storedPosition);
        return [lat, lng];
    }

    // Default to Berlin
    return [52.51843039016386, 13.395199187248908];
}

function Map() {

    const [airports, setAirports] = useState([]);
    const [showAirportCard, setShowAirportCard] = useState(false);
    const [airportId, setAirportId] = useState(null);
    const [mapPosition, setMapPosition] = useState(getMapPosition());
    const [coordinates, setCoordinates] = useState(null);
    const [focusAirport, setFocusAirport] = useState(null);

    const setAirportsRef = useRef(null);

    useEffect(() => {
        setAirportsRef.current = setAirports;
        window.setAirportsData = (data) => {
            if (typeof setAirportsRef.current === 'function') {
                setAirports(data);
                setAirportsRef.current(data);
            }
        };

        window.setFocusAirport = (icao) => {
            setFocusAirport(icao);
        };
    
        // Dispatch a custom event when the map is ready
        const event = new Event('mapReady');
        window.dispatchEvent(event);
    
        return () => {
            delete window.setAirportsData;
        };
    }, []);

    useEffect(() => {
        if (focusAirport !== null) {
            setCoordinates([airports[focusAirport].lat, airports[focusAirport].lon]);
            setAirportId(airports[focusAirport].id);
            setShowAirportCard(true);
        }
    }, [focusAirport]);

    const eventHandlers = (airport) => ({
        click: (e) => {
            setCoordinates([airport.lat, airport.lon]);
            setAirportId(airport.id);
            setShowAirportCard(true);
        },
        flyTo: (e) => {
            setCoordinates([airport.lat, airport.lon]);
        }
    });

    const iconCreateFunction = (cluster) => {
        // if url not ends with /top or /search, set style to 'inverted'
        var style = '';
        if (!window.location.pathname.endsWith('/top') && !window.location.pathname.endsWith('/search')) {
            style = 'inverted';
        }
        
        return L.divIcon({ 
            iconSize: [40, 40], 
            html: `<div class="leaflet-marker-icon marker-cluster ${style}">${cluster.getChildCount()}</div>` 
        });
    };

    return (
        <>
        <MapContainer 
            className="map" 
            center={mapPosition}
            zoom={4} 
            attributionControl={false} 
            zoomControl={false}
            maxBounds={[[-85, -360], [85, 360]]}
        >
            <TileLayer
                url="https://{s}.basemaps.cartocdn.com/dark_nolabels/{z}/{x}/{y}{r}.png"
                minZoom={3}
                maxZoom={17}
            />
            <MarkerClusterGroup showCoverageOnHover={false} maxClusterRadius={40} iconCreateFunction={iconCreateFunction}>
                {Object.keys(airports).map(key => {
                    const airport = airports[key];
                    const icon = createIcon(airport.color, airport.type);
                    return (
                        <Marker
                            key={airport.id}
                            position={[airport.lat, airport.lon]}
                            icon={icon}
                            eventHandlers={eventHandlers(airport)}
                        >
                            <Tooltip
                                direction="left"
                                className="airport"
                                interactive={true}
                                permanent
                            >
                                <span style={{ color: airport.color }}>
                                    {airport.icao}
                                </span>
                            </Tooltip>
                        </Marker>
                    );
                })}
            </MarkerClusterGroup>
            <SaveViewEvent />
            <PanEvent flyToCoordinates={coordinates} />
        </MapContainer>
        {showAirportCard && <PopupContainer airportId={airportId} />}
        </>
    );
}

export default Map;

const root = ReactDOM.createRoot(document.getElementById('map'));
root.render(
    <Map />
);
