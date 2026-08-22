import { useEffect } from 'react';

import { useMapGL } from '../context/MapGLContext';
import { insertBefore } from './mapConfig';

const SOURCE = 'weather';
const LAYER = 'weather-radar';
const INDEX_URL = 'https://api.rainviewer.com/public/weather-maps.json';

// RainViewer publishes a new radar frame roughly every 10 minutes.
const REFRESH_MS = 5 * 60 * 1000;

// RainViewer serves a literal "Zoom Level Not Supported" PNG above tile z7 — at either tile
// size, it is a data-resolution cap, not a tile-size one. Capping the source here means
// MapLibre overzooms the deepest real tiles instead of ever requesting that image.
const MAX_TILE_ZOOM = 7;

// Two levels of overzoom still reads as radar; beyond that it is misleading mush, so the layer
// switches itself off and comes back on the way down. maxzoom is exclusive.
const MAX_LAYER_ZOOM = 9;

// 512px tiles, colour scheme 8 (Dark Sky — built for dark basemaps), smoothed, snow shown.
const frameTiles = (host, path) => [`${host}${path}/512/{z}/{x}/{y}/8/1_1.png`];

// The newest available frame: nowcast when RainViewer is publishing one, else the latest past.
const latestFrame = (index) => {
    const radar = index?.radar ?? {};
    const frames = [...(radar.past ?? []), ...(radar.nowcast ?? [])];

    return frames.length ? frames[frames.length - 1].path : null;
};

const MapWeather = ({ onStatus }) => {

    const map = useMapGL();

    useEffect(() => {
        let cancelled = false;
        let timer = null;
        let currentPath = null;
        let dataStatus = 'loading';

        const publish = () => {
            if (!cancelled) { onStatus(dataStatus); }
        };

        const apply = (index) => {
            const path = latestFrame(index);

            if (cancelled || !path) {
                dataStatus = 'error';
                publish();

                return;
            }

            if (path === currentPath) {
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
                tileSize: 512,
                maxzoom: MAX_TILE_ZOOM,
                attribution: '<a href="https://www.rainviewer.com/" target="_blank">RainViewer</a>',
            });

            // Above the basemap and terrain, below the night shading and every airport layer —
            // so ICAO labels always stay legible over rain.
            map.addLayer({
                id: LAYER,
                type: 'raster',
                source: SOURCE,
                maxzoom: MAX_LAYER_ZOOM,
                // 0.5 is what keeps ICAO labels readable where they cross a cell — the labels
                // carry no outline of their own.
                paint: { 'raster-opacity': 0.5 },
            }, insertBefore(map, ['terminator', 'airports-hit']));
        };

        const refresh = () => {
            fetch(INDEX_URL, { cache: 'no-store' })
                .then((response) => response.json())
                .then(apply)
                .catch(() => {
                    // A failed refresh keeps the frame already on screen; only say error when
                    // there has never been one.
                    if (!currentPath) { dataStatus = 'error'; }
                    publish();
                });
        };

        const onSourceData = (event) => {
            if (event.sourceId === SOURCE && event.isSourceLoaded) {
                dataStatus = 'live';
                publish();
            }
        };

        const onSourceError = (event) => {
            if (event.sourceId === SOURCE) {
                dataStatus = 'error';
                publish();
            }
        };

        map.on('sourcedata', onSourceData);
        map.on('error', onSourceError);

        publish();
        refresh();
        timer = setInterval(refresh, REFRESH_MS);

        return () => {
            cancelled = true;
            clearInterval(timer);
            map.off('sourcedata', onSourceData);
            map.off('error', onSourceError);

            if (map.getLayer(LAYER)) { map.removeLayer(LAYER); }
            if (map.getSource(SOURCE)) { map.removeSource(SOURCE); }

            onStatus('loading');
        };
    }, [map, onStatus]);

    return null;
};

export default MapWeather;
