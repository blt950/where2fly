import { DivIcon } from 'leaflet';
import React, { useState, useEffect } from 'react';
import ReactDOM from 'react-dom/client';
import { MapContainer, TileLayer, Marker, Tooltip, useMap, useMapEvents } from 'react-leaflet'
import PopupContainer from './PopupContainer';
import MarkerClusterGroup from 'react-leaflet-cluster';

const createIcon = (color = '#ddb81c', airportType = 'large_airport') => {
    let sizePx = 10;

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

const MapComponent = () => {
    const map = useMapEvents({
        moveend() {
            localStorage.setItem('mapPosition', JSON.stringify(map.getCenter()));
        },
    });

    return null;
};

const getMapPosition = () => {
    var storedPosition = localStorage.getItem('mapPosition');
    if (storedPosition) {
        const { lat, lng } = JSON.parse(storedPosition);
        return [lat, lng];
    }
    return [52.51843039016386, 13.395199187248908];
}

function Map() {

    const [airports, setAirports] = useState([]);
    const [showAirportCard, setShowAirportCard] = useState(false);
    const [airportId, setAirportId] = useState(null);
    const [mapPosition, setMapPosition] = useState(getMapPosition());

    useEffect(() => {
        // Fetch the user's airports list
        fetch(route('api.lists.airports'))
            .then(response => response.json())
            .then(data => setAirports(data.data))
            .catch(error => console.error(error.message));
    }, []);

    const eventHandlers = (airportId) => ({
        click: (e) => {
            setAirportId(airportId);
            setShowAirportCard(true);
        }
    });

    const iconCreateFunction = (cluster) => {
        const style = 'inverted';
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
                            eventHandlers={eventHandlers(airport.id)}
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
            <MapComponent />
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