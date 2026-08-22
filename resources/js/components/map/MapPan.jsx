import { useEffect } from 'react';

import { useMapGL } from '../context/MapGLContext';

const MapPan = ({ flyToCoordinates }) => {

    const map = useMapGL();

    useEffect(() => {
        if (flyToCoordinates) {
            // MapLibre counts the duration in milliseconds; Leaflet counted seconds.
            map.panTo(flyToCoordinates, { duration: 500 });
        }
    }, [map, flyToCoordinates]);

    return null;
};

export default MapPan;
