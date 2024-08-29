import { DivIcon } from 'leaflet';
import React from 'react';
import ReactDOM from 'react-dom/client';
import { MapContainer, TileLayer, Marker, Tooltip, useMap } from 'react-leaflet'
import AirportCard from './AirportCard';
import { useState } from 'react';
import PopupContainer from './PopupContainer';

// Default lat and lon (Berlin, Europe)
var lat = 52.51843039016386;
var lon = 13.395199187248908;

const icon = new DivIcon({
    iconSize: [10, 10],
    html: `<span style="display: block; width: 10px; height: 10px; border-radius: 50%; background: #ddb81c"></span>`
});

function Map() {

    const [showAirportCard, setShowAirportCard] = useState(false);

    const eventHandlers = {
        click: (e) => {
            console.log("hi")
            setShowAirportCard(true);
        }
    };

    return (
        <>
        <MapContainer className="map" center={[lat, lon]} zoom={4} attributionControl={false} zoomControl={false} >
            <TileLayer
                url="https://{s}.basemaps.cartocdn.com/dark_nolabels/{z}/{x}/{y}{r}.png"
            />
            <Marker position={[51.505, -0.09]} icon={icon}>
                <Tooltip direction="left" className="airport" interactive={true} permanent>
                    EGLL
                </Tooltip>
            </Marker>
            <Marker position={[71.505, -0.09]} icon={icon} eventHandlers={eventHandlers}>
                <Tooltip direction="left" className="airport" interactive={true} permanent>
                    EGLL
                </Tooltip>
            </Marker>
        </MapContainer>
        <PopupContainer showAirportCard={showAirportCard} />
        </>
    );
}

export default Map;

const root = ReactDOM.createRoot(document.getElementById('map'));
root.render(
    <Map />
);