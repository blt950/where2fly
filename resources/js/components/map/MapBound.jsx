import { useEffect } from 'react';

import { paddingFor, useMapLayout } from '../context/MapLayoutContext';
import { useMapGL } from '../context/MapGLContext';
import { boundsFromCoordinates } from '../utils/geodesic';

const MapBound = ({ mapBounds }) => {

    const map = useMapGL();
    const { getInsets } = useMapLayout();

    useEffect(() => {
        if (!mapBounds?.length) {
            return;
        }

        // Frames the airports in whatever the drawer or card stack leaves uncovered
        map.fitBounds(boundsFromCoordinates(mapBounds), { padding: fitPadding(map, getInsets()), animate: false });
    }, [map, mapBounds, getInsets]);

    return null;
};

// Covered edges plus a margin, unless that leaves no room: then the margin alone
const fitPadding = (map, insets) => {
    const padding = paddingFor(insets);
    const { clientWidth, clientHeight } = map.getContainer();

    return padding.left + padding.right < clientWidth - 50 && padding.top + padding.bottom < clientHeight - 50
        ? padding
        : paddingFor({ top: 0, right: 0, bottom: 0, left: 0 });
};

export { fitPadding };
export default MapBound;
