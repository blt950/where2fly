import { useEffect, useRef } from 'react';

import { useMapGL } from '../context/MapGLContext';
import { cameraView } from './mapScene';

// The constructor already placed the first scene's camera; this moves it for every later one.
// `fit` is left to MapBound, which frames the airports once they arrive.
const MapCamera = ({ camera, sceneKey }) => {

    const map = useMapGL();
    const appliedKey = useRef(sceneKey);

    useEffect(() => {
        if (appliedKey.current === sceneKey) {
            return;
        }

        appliedKey.current = sceneKey;

        if (camera?.type === 'fit') {
            return;
        }

        map.easeTo({ ...cameraView(camera), duration: 600 });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [map, sceneKey]);

    return null;
};

export default MapCamera;
