import { useEffect } from 'react';
import { useMap } from 'react-leaflet';

function MapBound({ mapBounds }) {
    const map = useMap();

    useEffect(() => {
        if(mapBounds !== undefined){
            map.fitBounds(mapBounds, {padding: [50, 50], animate: false, duration: 0})
        }
    }, [mapBounds]);

    return null;
}

export default MapBound;