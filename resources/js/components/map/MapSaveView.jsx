import { useEffect } from 'react';

import { useMapGL } from '../context/MapGLContext';

const MapSaveView = () => {

    const map = useMapGL();

    useEffect(() => {
        // LngLat serialises to {lng, lat} — the same shape Leaflet's LatLng wrote, so existing
        // saved positions keep working without a migration.
        const save = () => localStorage.setItem('mapPosition', JSON.stringify(map.getCenter()));

        map.on('moveend', save);

        return () => { map.off('moveend', save); };
    }, [map]);

    return null;
};

export default MapSaveView;
