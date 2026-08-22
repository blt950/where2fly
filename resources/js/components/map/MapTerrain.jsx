import { useEffect } from 'react';

import { useMapGL } from '../context/MapGLContext';
import { insertBefore } from './mapConfig';

const SOURCE = 'dem';
const LAYER = 'hillshade';
const MapTerrain = ({ hillshade }) => {

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
            paint: hillshade,
        }, insertBefore(map, ['water', 'boundary_country_inner', 'terminator', 'airports-hit']));

        return () => {
            if (map.getLayer(LAYER)) { map.removeLayer(LAYER); }
            if (map.getSource(SOURCE)) { map.removeSource(SOURCE); }
        };
    }, [map, hillshade]);

    return null;
};

export default MapTerrain;
