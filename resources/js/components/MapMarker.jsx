import React, { useState, useEffect, useRef, useContext } from 'react';
import { Marker, Tooltip } from 'react-leaflet';
import { createIcon } from './utils/icon'; // Assuming these functions are defined in utils.js
import { useMapEvents } from 'react-leaflet';
import { set } from 'lodash';
import DrawRoute from './DrawRoute';
import { FocusAirportContext } from './context/FocusAirportContext';
import { MapContext } from './context/MapContext';

const MapMarker = ({ airport }) => {

    const [zoomLevel, setZoomLevel] = useState(0);
    const [showTooltip, setShowTooltip] = useState(true);
    const [ignoreZoomFilter, setIgnoreZoomFilter] = useState(false);
    const [color, setColor] = useState(airport.color);
    const { airports, focusAirport, setFocusAirport } = useContext(MapContext);

    const map = useMapEvents({
        zoomend() {
            setZoomLevel(map.getZoom());
        },
    });

    useEffect(() => {
        if(route().current('search') || route().current() === undefined){
            if(airport.type == 'small_airport'){
                (zoomLevel >= 8) ? setShowTooltip(true) : setShowTooltip(false);
            } else if(airport.type == 'medium_airport'){
                (zoomLevel > 5) ? setShowTooltip(true) : setShowTooltip(false);
            } else {
                setShowTooltip(true);
            }
        }
    }, [zoomLevel]);

    useEffect(() => {
        if(focusAirport === airport.icao){
            setColor('#ddb81c');
            setIgnoreZoomFilter(true);
        } else {
            setColor(airport.color);
            setIgnoreZoomFilter(false);
        }
    }, [focusAirport]);

    const eventHandlers = (airport) => ({
        click: (e) => {
            setFocusAirport(airport.icao);
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