import { useState, useEffect, useContext, useMemo } from 'react';
import { Marker, Tooltip } from 'react-leaflet';
import { createMarkerIcon } from '../utils/MarkerIcon';
import { useMapEvents } from 'react-leaflet';
import { MapContext } from '../context/MapContext';

const MapMarker = ({ airport }) => {
    const { focusAirport, setFocusAirport, primaryAirport } = useContext(MapContext);

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

        if(primaryAirport === airport.icao){
            setIgnoreZoomFilter(true);
        }
    }, [focusAirport]);

    // When you click on an airport, set it as the focus airport
    const eventHandlers = useMemo(() => ({
        click: (e) => {
            setFocusAirport(airport.icao);
        }
    }), [airport.icao]);

    const icon = useMemo(() => createMarkerIcon(color, airport.type), [color, airport.type]);

    return useMemo(() => (
        <Marker
            key={airport.id}
            position={[airport.lat, airport.lon]}
            icon={icon}
            renderer={L.canvas()}
            eventHandlers={eventHandlers}
        >
            {(ignoreZoomFilter || showTooltip) && (
                <Tooltip
                    direction="left"
                    className="airport"
                    interactive={true}
                    renderer={L.canvas()}
                    permanent
                >
                    <span style={{ color: color }}>
                        {airport.icao}
                    </span>
                </Tooltip>
            )}
        </Marker>
    ), [airport.id, airport.lat, airport.lon, icon, eventHandlers, ignoreZoomFilter, showTooltip, color, airport.icao]);
};

export default MapMarker;