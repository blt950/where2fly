import { useEffect } from 'react';
import { useMapGL } from '../context/MapGLContext';
import { beneath } from './mapConfig';
import { terminatorPolygon } from '../utils/solarTerminator';

const REFRESH_MS = 60_000;

const MapTerminator = () => {

    const map = useMapGL();

    useEffect(() => {
        map.addSource('terminator', { type: 'geojson', data: terminatorPolygon() });
        map.addLayer({
            id: 'terminator',
            type: 'fill',
            source: 'terminator',
            paint: {
                'fill-color': '#000000',
                'fill-opacity': 0.3,
                'fill-antialias': false,
            },
        }, beneath(map, ['airports-hit']));

        const timer = setInterval(() => {
            map.getSource('terminator')?.setData(terminatorPolygon());
        }, REFRESH_MS);

        return () => {
            clearInterval(timer);
            if (map.getLayer('terminator')) { map.removeLayer('terminator'); }
            if (map.getSource('terminator')) { map.removeSource('terminator'); }
        };
    }, [map]);

    return null;
};

export default MapTerminator;
