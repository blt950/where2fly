import { useEffect, useState } from 'react';
import { createPortal } from 'react-dom';

import { useMapGL } from '../context/MapGLContext';

// The map fills the viewport, so a credit inside it competes with the map itself. This renders
// the same information into the page footer, which is where the site's other credits live.
const FOOTER_SLOT = 'map-attribution';

// Anything that can add, remove or hide a source.
const EVENTS = ['styledata', 'sourcedata', 'zoomend'];

// The credit for every source currently being drawn. Layers outside their zoom range are
// skipped, so the radar and terrain credits come and go with the layers themselves.
const collectAttributions = (map) => {
    const zoom = map.getZoom();
    const inRange = ({ minzoom = -Infinity, maxzoom = Infinity }) => zoom >= minzoom && zoom < maxzoom;

    const sources = new Set(map.getStyle().layers
        .filter((layer) => layer.source && layer.layout?.visibility !== 'none' && inRange(layer))
        .map((layer) => layer.source));

    return [...new Set([...sources].map((id) => map.getSource(id)?.attribution).filter(Boolean))].join(', ');
};

const MapAttribution = () => {

    const map = useMapGL();
    const [attribution, setAttribution] = useState('');

    useEffect(() => {
        const refresh = () => setAttribution(collectAttributions(map));

        EVENTS.forEach((event) => map.on(event, refresh));
        refresh();

        return () => { EVENTS.forEach((event) => map.off(event, refresh)); };
    }, [map]);

    const slot = document.getElementById(FOOTER_SLOT);

    if (!slot || !attribution) {
        return null;
    }

    return createPortal(
        <>
            Map powered by <a href="https://maplibre.org/" target="_blank" rel="noopener">MapLibre</a>
            {', '}
            {/* Source-supplied HTML: CARTO's own TileJSON plus the strings we set on our sources. */}
            <span dangerouslySetInnerHTML={{ __html: attribution }} />
        </>,
        slot,
    );
};

export default MapAttribution;
