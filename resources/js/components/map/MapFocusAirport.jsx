import { useEffect } from 'react';

import { labelSpec } from '../utils/airportLayerSpec';
import { useMapLayer } from './mapLayers';

const DOT = 'focus-airport';
const LABEL = 'focus-airport-label';

const pointOf = (airport) => ({
    type: 'Feature',
    properties: { icao: airport.icao, color: '' },
    geometry: { type: 'Point', coordinates: [Number(airport.lon), Number(airport.lat)] },
});

// The focused airport, drawn on its own unclustered source above everything else. Inside a
// cluster bubble it would otherwise not be drawn at all, so selecting it showed nothing on the map.
const MapFocusAirport = ({ airport, color }) => {

    const data = { type: 'FeatureCollection', features: [pointOf(airport)] };

    const map = useMapLayer({
        id: DOT,
        source: { type: 'geojson', data },
        layer: {
            type: 'circle',
            paint: { 'circle-radius': 6, 'circle-color': color, 'circle-stroke-width': 2, 'circle-stroke-color': '#000000' },
        },
    }, [color]);

    // labelSpec carries an undefined filter, which style validation rejects
    // eslint-disable-next-line no-unused-vars
    const { filter, ...label } = labelSpec({ id: LABEL, source: LABEL, overlap: true });

    useMapLayer({
        id: LABEL,
        source: { type: 'geojson', data },
        layer: { ...label, paint: { 'text-color': color } },
    }, [color]);

    useEffect(() => {
        [DOT, LABEL].forEach((id) => map.getSource(id)?.setData(data));
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [map, airport.icao, airport.lon, airport.lat]);

    return null;
};

export default MapFocusAirport;
