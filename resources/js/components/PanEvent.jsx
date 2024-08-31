import React, { useEffect } from 'react';
import { useMap } from 'react-leaflet';

function PanEvent({ flyToCoordinates }) {
    const map = useMap();

    useEffect(() => {
        if (flyToCoordinates) {
            map.panTo(flyToCoordinates, { animate: true, duration: 0.5, easeLinearity: 0.25 });
        }
    }, [flyToCoordinates, map]);

    return null;
}

export default PanEvent;