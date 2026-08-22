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

        // No zoom gate: seeing at a glance where the terrain is, is most useful on a
        // continental overview. The layer stays opt-in and off by default, so DEM tiles are
        // only ever fetched by someone who asked for them.
        //
        // Exaggeration is stronger when zoomed out, where the DEM is downsampled and relief
        // would otherwise wash out; it eases back as real detail arrives.
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
            // Beneath the water fill, which is opaque: the terrarium DEM carries bathymetry, and
            // without this the ocean floor gets hillshaded too and the sea stops being flat.
            // The fallbacks matter because CARTO owns those ids and the terminator is optional.
        }, insertBefore(map, ['water', 'boundary_country_inner', 'terminator', 'airports-hit']));

        return () => {
            if (map.getLayer(LAYER)) { map.removeLayer(LAYER); }
            if (map.getSource(SOURCE)) { map.removeSource(SOURCE); }
        };
    }, [map]);

    return null;
};

export default MapTerrain;
