import { useEffect, useState } from 'react';
import { createPortal } from 'react-dom';

import { useMapGL } from '../context/MapGLContext';

// The map fills the viewport, so a credit inside it competes with the map itself. This renders
// the same information into the page footer, which is where the site's other credits live.
const FOOTER_SLOT = 'map-attribution';

const visibleSourceIds = (map) => {
    const zoom = map.getZoom();
    const ids = new Set();

    map.getStyle().layers.forEach((layer) => {
        if (!layer.source || layer.layout?.visibility === 'none') {
            return;
        }

        if (layer.minzoom !== undefined && zoom < layer.minzoom) {
            return;
        }

        if (layer.maxzoom !== undefined && zoom >= layer.maxzoom) {
            return;
        }

        ids.add(layer.source);
    });

    return ids;
};

const collectAttributions = (map) => {
    const seen = [];

    visibleSourceIds(map).forEach((id) => {
        const attribution = map.getSource(id)?.attribution;

        if (attribution && !seen.includes(attribution)) {
            seen.push(attribution);
        }
    });

    return seen.join(', ');
};

const MapAttribution = () => {

    const map = useMapGL();
    const [attribution, setAttribution] = useState('');

    useEffect(() => {
        const refresh = () => setAttribution((current) => {
            const next = collectAttributions(map);

            return next === current ? current : next;
        });

        map.on('styledata', refresh);
        map.on('sourcedata', refresh);
        map.on('zoomend', refresh);
        refresh();

        return () => {
            map.off('styledata', refresh);
            map.off('sourcedata', refresh);
            map.off('zoomend', refresh);
        };
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
