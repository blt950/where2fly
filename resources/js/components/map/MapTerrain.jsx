import { AIRPORT_SOURCES, hitId } from '../utils/airportLayerSpec';
import { BASEMAP_ANCHORS, TERMINATOR_LAYER } from './mapConfig';
import { useMapLayer } from './mapLayers';

const SOURCE = 'dem';
const LAYER = 'hillshade';

const MapTerrain = ({ hillshade }) => {

    useMapLayer({
        id: LAYER,
        sourceId: SOURCE,
        source: {
            type: 'raster-dem',
            tileSize: 256,
            maxzoom: 13,
            encoding: 'terrarium',
            tiles: ['https://s3.amazonaws.com/elevation-tiles-prod/terrarium/{z}/{x}/{y}.png'],
            attribution: '<a href="https://github.com/tilezen/joerd/blob/master/docs/attribution.md">Tilezen Joerd</a>',
        },
        layer: { type: 'hillshade', paint: hillshade },
        below: [
            BASEMAP_ANCHORS.water,
            BASEMAP_ANCHORS.countryBorders,
            TERMINATOR_LAYER,
            hitId(AIRPORT_SOURCES.results),
        ],
    }, [hillshade]);

    return null;
};

export default MapTerrain;
