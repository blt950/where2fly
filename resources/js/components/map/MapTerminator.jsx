import { useEffect } from 'react';

import { AIRPORT_SOURCES, hitId } from '../utils/airportLayerSpec';
import { terminatorPolygon } from '../utils/solarTerminator';
import { TERMINATOR_LAYER } from './mapConfig';
import { useMapLayer } from './mapLayers';

const REFRESH_MS = 60_000;

const MapTerminator = () => {

    const map = useMapLayer({
        id: TERMINATOR_LAYER,
        source: { type: 'geojson', data: terminatorPolygon() },
        layer: {
            type: 'fill',
            paint: {
                'fill-color': '#000000',
                'fill-opacity': 0.3,
                'fill-antialias': false,
            },
        },
        below: [hitId(AIRPORT_SOURCES.results)],
    });

    useEffect(() => {
        const timer = setInterval(() => {
            map.getSource(TERMINATOR_LAYER)?.setData(terminatorPolygon());
        }, REFRESH_MS);

        return () => clearInterval(timer);
    }, [map]);

    return null;
};

export default MapTerminator;
