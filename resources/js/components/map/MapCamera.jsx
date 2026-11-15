import { useEffect, useRef } from 'react';

import { offsetFor, useMapLayout } from '../context/MapLayoutContext';
import { useMapGL } from '../context/MapGLContext';
import { cameraView } from './mapScene';

// The constructor already placed the first scene's camera; this moves it for every later one.
// `fit` is left to MapBound, which frames the airports once they arrive.
const MapCamera = ({ camera, sceneVersion }) => {

    const map = useMapGL();
    const { getInsets } = useMapLayout();
    const appliedVersion = useRef(sceneVersion);

    useEffect(() => {
        if (appliedVersion.current === sceneVersion) {
            return;
        }

        appliedVersion.current = sceneVersion;

        if (camera?.type === 'fit') {
            return;
        }

        map.easeTo({ ...cameraView(camera), offset: offsetFor(getInsets()), duration: 600 });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [map, sceneVersion]);

    return null;
};

export default MapCamera;
