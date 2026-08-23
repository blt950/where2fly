import { useEffect } from 'react';

import { useMapGL } from '../context/MapGLContext';
import { boundsFromCoordinates } from '../utils/geodesic';

const MapBound = ({ mapBounds }) => {

    const map = useMapGL();

    useEffect(() => {
        if (!mapBounds?.length) {
            return;
        }

        map.fitBounds(boundsFromCoordinates(mapBounds), { padding: 50, animate: false });
    }, [map, mapBounds]);

    return null;
};

export default MapBound;
