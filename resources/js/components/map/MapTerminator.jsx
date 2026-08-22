import { useEffect } from 'react';

import { useMapGL } from '../context/MapGLContext';
import { terminatorPolygon } from '../utils/solarTerminator';

// The terminator drifts a quarter degree of longitude a minute — under a pixel at the zooms
// this map lives at, so a minute between recomputes is plenty. The Leaflet version it replaces
// computed once on mount and went stale for the rest of the session.
const REFRESH_MS = 60_000;

const MapTerminator = () => {

    const map = useMapGL();

    useEffect(() => {
        map.addSource('terminator', { type: 'geojson', data: terminatorPolygon() });

        // Mounted before the airport layers so it lands at the bottom of the stack. The guard
        // covers a remount, where those layers already exist and would otherwise be covered.
        map.addLayer({
            id: 'terminator',
            type: 'fill',
            source: 'terminator',
            paint: {
                'fill-color': '#000000',
                'fill-opacity': 0.3,
                'fill-antialias': false,
            },
        }, map.getLayer('airports-hit') ? 'airports-hit' : undefined);

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
