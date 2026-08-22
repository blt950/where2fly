import { useContext, useEffect } from 'react';

import { MapContext } from '../context/MapContext';
import { useMapGL } from '../context/MapGLContext';
import { arcDegrees, boundsFromCoordinates, greatCircle } from '../utils/geodesic';

const SOURCE = 'route';
const LAYER = 'route-line';
const COLOR = '#ddb81c';

const easeInOutCubic = (t) => (t < 0.5 ? 4 * t ** 3 : 1 - (-2 * t + 2) ** 3 / 2);

// Leaflet's paddingTopLeft/paddingBottomRight, as MapLibre's padding object. Below the md
// breakpoint the old code never flew at all, and .map is display:none there anyway.
const framePadding = (width) => {
    if (width > 1920) { return { left: 400, top: 350, right: 75, bottom: 50 }; }
    if (width > 767) { return { left: 50, top: 350, right: 50, bottom: 50 }; }

    return null;
};

const MapRoute = ({ departure, arrival, reverseDirection = false }) => {

    const map = useMapGL();
    const { airports } = useContext(MapContext);

    useEffect(() => {
        const from = airports[reverseDirection ? arrival : departure];
        const to = airports[reverseDirection ? departure : arrival];

        if (!from || !to) {
            return undefined;
        }

        const start = [Number(from.lon), Number(from.lat)];
        const end = [Number(to.lon), Number(to.lat)];
        const line = greatCircle(start, end);

        // lineMetrics is what makes line-progress — and therefore the reveal — available.
        map.addSource(SOURCE, {
            type: 'geojson',
            lineMetrics: true,
            data: { type: 'Feature', properties: {}, geometry: { type: 'LineString', coordinates: line } },
        });

        map.addLayer({
            id: LAYER,
            type: 'line',
            source: SOURCE,
            layout: { 'line-cap': 'round', 'line-join': 'round' },
            paint: { 'line-color': COLOR, 'line-width': 2 },
        });

        // The original read Math.sqrt(Math.log(r)) with r in planar degrees, which is NaN for
        // any route under a degree long. Floor the argument at e so the result floors at 200ms.
        const duration = Math.sqrt(Math.log(Math.max(arcDegrees(start, end), Math.E))) * 200;

        let frame = null;
        let startedAt = null;

        const reveal = (timestamp) => {
            startedAt ??= timestamp;
            const progress = Math.min(1, easeInOutCubic((timestamp - startedAt) / duration));

            if (progress >= 1) {
                // Back to the flat line-color, so the steady state costs nothing.
                map.setPaintProperty(LAYER, 'line-gradient', undefined);
                return;
            }

            // Gradient stops must be strictly ascending or the whole paint update is dropped.
            const head = Math.min(Math.max(progress, 0.001), 0.998);

            map.setPaintProperty(LAYER, 'line-gradient', [
                'interpolate', ['linear'], ['line-progress'],
                0, COLOR,
                head, COLOR,
                head + 0.001, 'rgba(221,184,28,0)',
                1, 'rgba(221,184,28,0)',
            ]);

            frame = requestAnimationFrame(reveal);
        };

        const startReveal = () => { frame = requestAnimationFrame(reveal); };
        const padding = framePadding(window.innerWidth);
        const width = map.getContainer().clientWidth;

        // MapLibre misbehaves when the horizontal padding meets or exceeds the canvas width.
        if (padding && padding.left + padding.right < width) {
            // cameraForBounds honours maxZoom but has no minZoom, so the floor is manual.
            const camera = map.cameraForBounds(boundsFromCoordinates(line), { padding, maxZoom: 7 });

            if (camera) {
                map.flyTo({ ...camera, zoom: Math.max(3, camera.zoom), duration: 350 });
            }
        }

        map.once('moveend', startReveal);

        return () => {
            if (frame) { cancelAnimationFrame(frame); }
            map.off('moveend', startReveal);
            if (map.getLayer(LAYER)) { map.removeLayer(LAYER); }
            if (map.getSource(SOURCE)) { map.removeSource(SOURCE); }
        };
    }, [map, airports, departure, arrival, reverseDirection]);

    return null;
};

export default MapRoute;
