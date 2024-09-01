import React, { useState, useEffect, useRef } from 'react';
import { Marker, Tooltip } from 'react-leaflet';
import { createIcon } from './utils/icon'; // Assuming these functions are defined in utils.js
import { useMapEvents } from 'react-leaflet';
import { set } from 'lodash';

const MapMarker = ({ airport, ignoreZoom, colorOverride }) => {

    const [zoomLevel, setZoomLevel] = useState(0);
    const [showTooltip, setShowTooltip] = useState(true);
    const [ignoreZoomFilter, setIgnoreZoomFilter] = useState(ignoreZoom || false);
    const [color, setColor] = useState(colorOverride || airport.color);

    const map = useMapEvents({
        zoomend() {
            setZoomLevel(map.getZoom());
        },
    });

    useEffect(() => {
        if(airport.type == 'small_airport'){
            (zoomLevel >= 8) ? setShowTooltip(true) : setShowTooltip(false);
        } else if(airport.type == 'medium_airport'){
            (zoomLevel > 5) ? setShowTooltip(true) : setShowTooltip(false);
        } else {
            setShowTooltip(true);
        }
    }, [zoomLevel]);

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

    const icon = createIcon(color, airport.type);

    return (
        <Marker
            key={airport.id}
            position={[airport.lat, airport.lon]}
            icon={icon}
            eventHandlers={eventHandlers(airport)}
        >
            {(ignoreZoomFilter || showTooltip) && (
                <Tooltip
                    direction="left"
                    className="airport"
                    interactive={true}
                    permanent
                >
                    <span style={{ color: color }}>
                        {airport.icao}
                    </span>
                </Tooltip>
            )}
        </Marker>
    );
};

export default MapMarker;