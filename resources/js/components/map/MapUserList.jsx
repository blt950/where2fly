import { useContext, useEffect } from 'react';

import { MapContext } from '../context/MapContext';
import { useMapGL } from '../context/MapGLContext';
import { LABEL_MINZOOM, labelSpec } from '../utils/airportLayerSpec';
import { airportsToGeoJson } from '../utils/airportsGeoJson';

const SOURCE = 'user-list';
const HIT = 'user-list-hit';
const DOTS = 'user-list-dots';

const LABELS = ['large_airport', 'medium_airport', 'small_airport']
    .map((airportType) => [`user-list-label-${airportType.replace('_airport', '')}`, airportType]);

const CLICKABLE = [HIT, ...LABELS.map(([id]) => id)];

const MapUserList = ({ listAirports, palette }) => {

    const map = useMapGL();
    const { setFocusAirport } = useContext(MapContext);

    useEffect(() => {
        map.addSource(SOURCE, { type: 'geojson', data: airportsToGeoJson(listAirports, palette) });

        // Same 9px target as the search airports — a small_airport dot is 5px across.
        map.addLayer({
            id: HIT,
            type: 'circle',
            source: SOURCE,
            paint: { 'circle-radius': 9, 'circle-opacity': 0 },
        });

        map.addLayer({
            id: DOTS,
            type: 'circle',
            source: SOURCE,
            paint: { 'circle-radius': ['get', 'r'], 'circle-color': ['to-color', ['get', 'color']] },
        });

        LABELS.forEach(([id, airportType]) => {
            map.addLayer(labelSpec({
                id,
                source: SOURCE,
                filter: ['==', ['get', 'type'], airportType],
                minzoom: LABEL_MINZOOM[airportType](),
            }));
        });

        const focus = (event) => setFocusAirport(event.features[0].properties.icao);
        const pointer = () => { map.getCanvas().style.cursor = 'pointer'; };
        const reset = () => { map.getCanvas().style.cursor = ''; };

        map.on('click', CLICKABLE, focus);
        map.on('mouseenter', CLICKABLE, pointer);
        map.on('mouseleave', CLICKABLE, reset);

        return () => {
            map.off('click', CLICKABLE, focus);
            map.off('mouseenter', CLICKABLE, pointer);
            map.off('mouseleave', CLICKABLE, reset);

            [...CLICKABLE, DOTS].forEach((id) => { if (map.getLayer(id)) { map.removeLayer(id); } });
            if (map.getSource(SOURCE)) { map.removeSource(SOURCE); }
        };
    }, [map, palette, setFocusAirport]);

    useEffect(() => {
        map.getSource(SOURCE)?.setData(airportsToGeoJson(listAirports, palette));
    }, [map, listAirports, palette]);

    return null;
};

export default MapUserList;
