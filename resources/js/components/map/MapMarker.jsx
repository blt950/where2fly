import { memo, useMemo } from 'react';
import { Marker, Tooltip } from 'react-leaflet';
import { createMarkerIcon } from '../utils/MarkerIcon';

const MapMarker = ({ airport, isFocused, isPrimary, setFocusAirport }) => {

    const color = isFocused ? '#ddb81c' : airport.color;

    const eventHandlers = useMemo(() => ({
        click: () => setFocusAirport(airport.icao),
    }), [airport.icao, setFocusAirport]);

    const icon = useMemo(() => createMarkerIcon(color, airport.type), [color, airport.type]);

    // The tooltip is always rendered; its visibility per zoom/type is handled
    // purely in CSS via classes on the map container (see map.scss + the
    // MapTooltipZoom component). This keeps zooming out of React entirely, so
    // markers never re-render and the cluster group is never rebuilt on zoom.
    const tooltipClass = `airport airport--${airport.type}${(isFocused || isPrimary) ? ' airport--pinned' : ''}`;

    return (
        <Marker
            position={[airport.lat, airport.lon]}
            icon={icon}
            eventHandlers={eventHandlers}
        >
            <Tooltip
                direction="left"
                className={tooltipClass}
                interactive={true}
                permanent
            >
                <span style={{ color: color }}>
                    {airport.icao}
                </span>
            </Tooltip>
        </Marker>
    );
};

// memo: a marker only re-renders when its own props change (color on focus,
// or the airports data).
export default memo(MapMarker);
