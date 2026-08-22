import { useEffect } from 'react';

import { useMapGL } from '../context/MapGLContext';
import { insertBefore } from './mapConfig';

const SOURCE = 'dem';
const LAYER = 'hillshade';
const MapTerrain = () => {

    const map = useMapGL();

    useEffect(() => {
        map.addSource(SOURCE, {
            type: 'raster-dem',
            tileSize: 256,
            maxzoom: 13,
            encoding: 'terrarium',
            tiles: ['https://s3.amazonaws.com/elevation-tiles-prod/terrarium/{z}/{x}/{y}.png'],
            attribution: '<a href="https://github.com/tilezen/joerd/blob/master/docs/attribution.md">Tilezen Joerd</a>',
        });

        map.addLayer({
            id: LAYER,
            type: 'hillshade',
            source: SOURCE,
            paint: {
                'hillshade-exaggeration': ['interpolate', ['linear'], ['zoom'], 0, 0.6, 5, 0.5, 9, 0.35],
                'hillshade-shadow-color': '#000000',
                'hillshade-highlight-color': '#3a4048',
                'hillshade-accent-color': '#000000',
            },
        }, insertBefore(map, ['water', 'boundary_country_inner', 'terminator', 'airports-hit']));

        return () => {
            if (map.getLayer(LAYER)) { map.removeLayer(LAYER); }
            if (map.getSource(SOURCE)) { map.removeSource(SOURCE); }
        };
    }, [map]);

    return null;
};

export default MapTerrain;
