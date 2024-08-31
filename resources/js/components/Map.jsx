import { DivIcon } from 'leaflet';
import React, { useState, useEffect } from 'react';
import ReactDOM from 'react-dom/client';
import { MapContainer, TileLayer, Marker, Tooltip, useMap } from 'react-leaflet'
import PopupContainer from './PopupContainer';

// Default lat and lon (Berlin, Europe)
var lat = 52.51843039016386;
var lon = 13.395199187248908;

const createIcon = (color = '#ddb81c') => new DivIcon({
    iconSize: [10, 10],
    html: `<span style="display: block; width: 10px; height: 10px; border-radius: 50%; background: ${color}"></span>`
});

function Map() {

    const [airports, setAirports] = useState([]);
    const [showAirportCard, setShowAirportCard] = useState(false);
    const [airportId, setAirportId] = useState(null);

    useEffect(() => {
        fetch(route('api.lists.airports'))
            .then(response => response.json())
            .then(data => setAirports(data.data))
            .catch(error => console.error(error.message));
    }, []);

    console.log("airports", airports);

    const eventHandlers = (airportId) => ({
        click: (e) => {
            setAirportId(airportId);
            setShowAirportCard(true);
        }
    });

    return (
        <>
        <MapContainer className="map" center={[lat, lon]} zoom={4} attributionControl={false} zoomControl={false} >
            <TileLayer
                url="https://{s}.basemaps.cartocdn.com/dark_nolabels/{z}/{x}/{y}{r}.png"
            />
            {Object.keys(airports).map(key => {
                const airport = airports[key];
                const icon = createIcon(airport.color);
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