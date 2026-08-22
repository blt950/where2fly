import { useEffect } from 'react';

import { useMapGL } from '../context/MapGLContext';

const MapSaveView = () => {
    const map = useMapGL();

    useEffect(() => {
        const save = () => localStorage.setItem('mapPosition', JSON.stringify(map.getCenter()));
        map.on('moveend', save);
        return () => { map.off('moveend', save); };
    }, [map]);

    return null;
};

export default MapSaveView;
