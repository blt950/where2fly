import { useEffect } from 'react';

import { useMapGL } from '../context/MapGLContext';
import { insertBefore } from './mapConfig';

const SOURCE = 'dem';
const LAYER = 'hillshade';

// With pitch locked at 0 setTerrain is all but invisible — relief only reads when the camera
// tilts — so this ships hillshade instead, which works flat.
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

        // minzoom 7 with the exaggeration ramping up from nothing is what keeps the dark look
        // intact: no DEM requests and no visual change at the z2-6 most sessions sit at. A
        // 1400x1000 viewport at z8 pulls roughly 24 tiles, about 1.5MB.
        map.addLayer({
            id: LAYER,
            type: 'hillshade',
            source: SOURCE,
            minzoom: 7,
            paint: {
                'hillshade-exaggeration': ['interpolate', ['linear'], ['zoom'], 7, 0, 9, 0.35],
                'hillshade-shadow-color': '#000000',
                'hillshade-highlight-color': '#3a4048',
                'hillshade-accent-color': '#000000',
            },
            // Under country borders but above land and water. The fallbacks matter because
            // CARTO owns that layer id and the terminator is user-toggleable.
        }, insertBefore(map, ['boundary_country_inner', 'terminator', 'airports-hit']));

        return () => {
            if (map.getLayer(LAYER)) { map.removeLayer(LAYER); }
            if (map.getSource(SOURCE)) { map.removeSource(SOURCE); }
        };
    }, [map]);

    return null;
};

export default MapTerrain;
