import { useEffect } from 'react';

import { offsetFor, useMapLayout } from '../context/MapLayoutContext';
import { useMapGL } from '../context/MapGLContext';

const MapPan = ({ flyToCoordinates }) => {

    const map = useMapGL();
    const { getInsets } = useMapLayout();

    useEffect(() => {
        if (flyToCoordinates) {
            // Centred in the uncovered part of the map, not under the drawer or card stack
            map.panTo(flyToCoordinates, { duration: 500, offset: offsetFor(getInsets()) });
        }
    }, [map, flyToCoordinates, getInsets]);

    return null;
};

export default MapPan;
