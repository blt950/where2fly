import { useEffect } from 'react';

import { useMapGL } from '../context/MapGLContext';
import { AIRPORT_SOURCES, FOCUS_LAYERS } from '../utils/airportLayerSpec';

// The focused airport's own marker counts too: inside a cluster it's the only thing to tap
const AIRPORT_SOURCE_IDS = [...Object.values(AIRPORT_SOURCES), ...Object.values(FOCUS_LAYERS)];

// A click on the map itself, not on an airport or a cluster (those have their own handlers)
const MapBackgroundClick = ({ onClick }) => {

    const map = useMapGL();

    useEffect(() => {
        if (!onClick) {
            return undefined;
        }

        const handle = (event) => {
            const hit = map.queryRenderedFeatures(event.point).some((feature) => AIRPORT_SOURCE_IDS.includes(feature.source));

            if (!hit) {
                onClick();
            }
        };

        map.on('click', handle);

        return () => { map.off('click', handle); };
    }, [map, onClick]);

    return null;
};

export default MapBackgroundClick;
