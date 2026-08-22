import { useEffect } from 'react';

import { useMapGL } from '../context/MapGLContext';

const MapPan = ({ flyToCoordinates }) => {

    const map = useMapGL();

    useEffect(() => {
        if (flyToCoordinates) {
            map.panTo(flyToCoordinates, { duration: 500 });
        }
    }, [map, flyToCoordinates]);

    return null;
};

export default MapPan;
