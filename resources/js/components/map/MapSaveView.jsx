import { useEffect } from 'react';

import { useMapGL } from '../context/MapGLContext';
import { writeStored } from '../utils/storage';

export const POSITION_KEY = 'mapPosition';

const MapSaveView = () => {
    const map = useMapGL();

    useEffect(() => {
        // getCenter() alone yields {lng, lat}; getInitMapPosition also restores the zoom.
        const save = () => writeStored(POSITION_KEY, { ...map.getCenter(), zoom: map.getZoom() });

        map.on('moveend', save);

        return () => { map.off('moveend', save); };
    }, [map]);

    return null;
};

export default MapSaveView;
