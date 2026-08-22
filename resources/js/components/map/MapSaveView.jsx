import { useEffect } from 'react';

import { useMapGL } from '../context/MapGLContext';

const MapSaveView = () => {
    const map = useMapGL();

    useEffect(() => {
        // getCenter() alone yields {lng, lat}; getInitMapPosition also restores the zoom.
        const save = () => localStorage.setItem('mapPosition',
            JSON.stringify({ ...map.getCenter(), zoom: map.getZoom() }));
        map.on('moveend', save);
        return () => { map.off('moveend', save); };
    }, [map]);

    return null;
};

export default MapSaveView;
