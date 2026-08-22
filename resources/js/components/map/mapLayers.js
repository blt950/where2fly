import { useEffect } from 'react';

import { useMapGL } from '../context/MapGLContext';
import { beneath } from './mapConfig';

// A style swap can take a layer and its source down before React unmounts the component that
// owns them, so every teardown checks first.
export const removeSourceLayer = (map, layerId, sourceId = layerId) => {
    if (map.getLayer(layerId)) { map.removeLayer(layerId); }
    if (map.getSource(sourceId)) { map.removeSource(sourceId); }
};

// One overlay — a source and the single layer drawing it — for as long as the component is
// mounted. `below` names candidate anchors, first existing one wins. Overlays that cannot add
// their source on mount (MapWeather waits on a fetch) call removeSourceLayer directly instead.
export const useMapLayer = ({ id, sourceId = id, source, layer, below = [] }, deps = []) => {

    const map = useMapGL();

    useEffect(() => {
        map.addSource(sourceId, source);
        map.addLayer({ id, source: sourceId, ...layer }, beneath(map, below));

        return () => removeSourceLayer(map, id, sourceId);
    }, [map, ...deps]);

    return map;
};
