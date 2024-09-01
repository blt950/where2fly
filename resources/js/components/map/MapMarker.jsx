import { useState, useEffect, useContext } from 'react';
import { Marker, Tooltip } from 'react-leaflet';
import { createMarkerIcon } from '../utils/MarkerIcon';
import { useMapEvents } from 'react-leaflet';
import { MapContext } from '../context/MapContext';

const MapMarker = ({ airport }) => {
    const { focusAirport, setFocusAirport } = useContext(MapContext);

    const [zoomLevel, setZoomLevel] = useState(0);
    const [showTooltip, setShowTooltip] = useState(true);
    const [ignoreZoomFilter, setIgnoreZoomFilter] = useState(false);
    const [color, setColor] = useState(airport.color);

    // Fetch zoom level
    const map = useMapEvents({
        zoomend() {
            setZoomLevel(map.getZoom());
        },
    });

    // When zoomlevel changes, hide/show tooltips based on airport size
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

    // When focusAirport changes, change color and ignore zoom filter
    useEffect(() => {
        if(focusAirport === airport.icao){
            setColor('#ddb81c');
            setIgnoreZoomFilter(true);
        } else {
            setColor(airport.color);
            setIgnoreZoomFilter(false);
        }
    }, [focusAirport]);

    // When you click on an airport, set it as the focus airport
    const eventHandlers = (airport) => ({
        click: (e) => {
            setFocusAirport(airport.icao);
        }
    });

    const icon = createMarkerIcon(color, airport.type);

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