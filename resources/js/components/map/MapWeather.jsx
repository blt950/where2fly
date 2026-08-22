import { useEffect } from 'react';

import { useMapGL } from '../context/MapGLContext';
import { insertBefore } from './mapConfig';

const SOURCE = 'weather';
const LAYER = 'weather-radar';
const INDEX_URL = 'https://api.rainviewer.com/public/weather-maps.json';

// RainViewer publishes a new radar frame roughly every 10 minutes.
const REFRESH_MS = 5 * 60 * 1000;

// 256px tiles, colour scheme 8 (Dark Sky — built for dark basemaps), smoothed, snow shown.
const frameTiles = (host, path) => [`${host}${path}/256/{z}/{x}/{y}/8/1_1.png`];

// The newest available frame: nowcast when RainViewer is publishing one, else the latest past.
const latestFrame = (index) => {
    const radar = index?.radar ?? {};
    const frames = [...(radar.past ?? []), ...(radar.nowcast ?? [])];

    return frames.length ? frames[frames.length - 1].path : null;
};

const MapWeather = () => {

    const map = useMapGL();

    useEffect(() => {
        let cancelled = false;
        let timer = null;
        let currentPath = null;

        const apply = (index) => {
            const path = latestFrame(index);

            if (cancelled || !path || path === currentPath) {
                return;
            }

            currentPath = path;
            const tiles = frameTiles(index.host, path);

            // setTiles swaps the frame in place; rebuilding the source would flash the layer.
            if (map.getSource(SOURCE)) {
                map.getSource(SOURCE).setTiles(tiles);

                return;
            }

            map.addSource(SOURCE, {
                type: 'raster',
                tiles,
                tileSize: 256,
                maxzoom: 12,
                attribution: '<a href="https://www.rainviewer.com/" target="_blank">RainViewer</a>',
            });

            // Above the basemap and terrain, below the night shading and the airports.
            map.addLayer({
                id: LAYER,
                type: 'raster',
                source: SOURCE,
                paint: { 'raster-opacity': 0.6 },
            }, insertBefore(map, ['terminator', 'airports-hit']));
        };

        const refresh = () => {
            fetch(INDEX_URL, { cache: 'no-store' })
                .then((response) => response.json())
                .then(apply)
                .catch(() => { /* radar is decorative; a failed refresh keeps the last frame */ });
        };

        refresh();
        timer = setInterval(refresh, REFRESH_MS);

        return () => {
            cancelled = true;
            clearInterval(timer);
            if (map.getLayer(LAYER)) { map.removeLayer(LAYER); }
            if (map.getSource(SOURCE)) { map.removeSource(SOURCE); }
        };
    }, [map]);

    return null;
};

export default MapWeather;
