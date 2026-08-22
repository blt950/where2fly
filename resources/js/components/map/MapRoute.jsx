import { useContext, useEffect } from 'react';
import { MapContext } from '../context/MapContext';
import { useMapGL } from '../context/MapGLContext';
import { arcDegrees, boundsFromCoordinates, greatCircle } from '../utils/geodesic';

const SOURCE = 'route';
const LAYER = 'route-line';

const transparent = (hex) => `rgba(${[1, 3, 5].map((i) => parseInt(hex.slice(i, i + 2), 16))}, 0)`;
const easeInOutCubic = (t) => (t < 0.5 ? 4 * t ** 3 : 1 - (-2 * t + 2) ** 3 / 2);
const revealTo = (head, color) => {
    const cut = Math.min(Math.max(head, 0.001), 0.998);
    const gone = transparent(color);

    return ['interpolate', ['linear'], ['line-progress'],
        0, color,
        cut, color,
        cut + 0.001, gone,
        1, gone];
};

const framePadding = (width) => {
    if (width > 1920) { return { left: 400, top: 350, right: 75, bottom: 50 }; }
    if (width > 767) { return { left: 50, top: 350, right: 50, bottom: 50 }; }

    return null;
};

const MapRoute = ({ departure, arrival, reverseDirection = false, color }) => {

    const map = useMapGL();
    const { findAirport } = useContext(MapContext);

    useEffect(() => {
        const from = findAirport(reverseDirection ? arrival : departure);
        const to = findAirport(reverseDirection ? departure : arrival);

        if (!from || !to) {
            return undefined;
        }

        const start = [Number(from.lon), Number(from.lat)];
        const end = [Number(to.lon), Number(to.lat)];
        const line = greatCircle(start, end);

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
            paint: { 'line-color': color, 'line-width': 2, 'line-gradient': revealTo(0, color) },
        });

        const duration = Math.sqrt(Math.log(Math.max(arcDegrees(start, end), Math.E))) * 200;

        let frame = null;
        let startedAt = null;

        const reveal = (timestamp) => {
            startedAt ??= timestamp;
            const progress = Math.min(1, easeInOutCubic((timestamp - startedAt) / duration));

            if (progress >= 1) {
                map.setPaintProperty(LAYER, 'line-gradient', undefined);
                return;
            }

            map.setPaintProperty(LAYER, 'line-gradient', revealTo(progress, color));
            frame = requestAnimationFrame(reveal);
        };

        const startReveal = () => { frame = requestAnimationFrame(reveal); };
        const padding = framePadding(window.innerWidth);
        const width = map.getContainer().clientWidth;
        let flying = false;

        if (padding && padding.left + padding.right < width) {
            const camera = map.cameraForBounds(boundsFromCoordinates(line), { padding, maxZoom: 7 });

            if (camera) {
                // Under prefers-reduced-motion flyTo falls through to jumpTo, which fires
                // moveend inside the call — listen first or the reveal never starts.
                map.once('moveend', startReveal);

                // Leaflet snapped the fitted zoom down to a whole level, which is where the
                // route's breathing room came from. MapLibre returns it fractional.
                map.flyTo({ ...camera, zoom: Math.floor(camera.zoom-0.3), duration: 350 });
                flying = true;
            }
        }

        if (!flying) {
            startReveal();
        }

        return () => {
            if (frame) { cancelAnimationFrame(frame); }
            map.off('moveend', startReveal);
            if (map.getLayer(LAYER)) { map.removeLayer(LAYER); }
            if (map.getSource(SOURCE)) { map.removeSource(SOURCE); }
        };
    }, [map, findAirport, departure, arrival, reverseDirection, color]);

    return null;
};

export default MapRoute;
