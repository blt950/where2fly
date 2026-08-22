import { useContext, useEffect } from 'react';

import { MapContext } from '../context/MapContext';
import { useMapGL } from '../context/MapGLContext';
import { LABEL_MINZOOM, labelSpec, ROOT_FONT_SIZE } from '../utils/airportLayerSpec';
import { airportsToGeoJson, EMPTY_FEATURE_COLLECTION } from '../utils/airportsGeoJson';
import { LABEL_FONT } from './mapConfig';

const SOURCE = 'airports';

// Every layer a click on an airport can land on. Leaflet forwarded clicks on the ICAO text to
// its marker (MapMarker set interactive on the tooltip), so the label layers belong here too.
const AIRPORT_LAYERS = ['airports-hit', 'airports-label-large', 'airports-label-medium',
    'airports-label-small', 'airports-label-pinned'];

const NOT_A_CLUSTER = ['!', ['has', 'point_count']];

// ClusterIcon.jsx scaled the bubble from 2rem to 3.75rem across ln(2)..ln(100), and interpolate
// clamps outside its stops for free — the Math.min/max the old ratio needed.
const clusterScale = (minRem, maxRem) => ['interpolate', ['linear'], ['ln', ['get', 'point_count']],
    Math.log(2), minRem * ROOT_FONT_SIZE, Math.log(100), maxRem * ROOT_FONT_SIZE];

const MapAirportLayers = ({ cluster, clusterRadius, palette }) => {

    const map = useMapGL();
    const { airports, focusAirport, primaryAirport, setFocusAirport } = useContext(MapContext);

    // cluster and clusterRadius are fixed when a GeoJSON source is created — setData cannot
    // change them — so changing either means tearing the source down and rebuilding.
    useEffect(() => {
        map.addSource(SOURCE, {
            type: 'geojson',
            data: airportsToGeoJson(airports, palette),
            cluster,
            clusterRadius,
            clusterMaxZoom: 12,
        });

        // A small_airport is a 5px click target. A transparent circle over it fixes that;
        // queryRenderedFeatures tests geometry, not alpha, so the clicks still land.
        map.addLayer({
            id: 'airports-hit',
            type: 'circle',
            source: SOURCE,
            filter: NOT_A_CLUSTER,
            paint: { 'circle-radius': 9, 'circle-opacity': 0 },
        });

        if (cluster) {
            map.addLayer({
                id: 'airports-clusters',
                type: 'circle',
                source: SOURCE,
                filter: ['has', 'point_count'],
                paint: {
                    'circle-radius': clusterScale(2 / 2, 3.75 / 2),
                    'circle-color': isDefaultView() ? '#2f3549' : '#ddb81c',
                },
            });

            map.addLayer({
                id: 'airports-cluster-count',
                type: 'symbol',
                source: SOURCE,
                filter: ['has', 'point_count'],
                layout: {
                    'text-field': ['get', 'point_count_abbreviated'],
                    'text-font': LABEL_FONT,
                    'text-size': clusterScale(0.75, 3.75 * 0.35),
                    'text-allow-overlap': true,
                },
                paint: { 'text-color': isDefaultView() ? '#ffffff' : '#000000' },
            });
        }

        map.addLayer({
            id: 'airports-dots',
            type: 'circle',
            source: SOURCE,
            filter: NOT_A_CLUSTER,
            // to-color is required: a ['get'] returns a string, and MapLibre will not coerce a
            // string-typed expression to a colour.
            paint: { 'circle-radius': ['get', 'r'], 'circle-color': ['to-color', ['get', 'color']] },
        });

        // Leaflet's zoom was integer, so its `zoom > 5` meant 6 and up. MapLibre's is
        // fractional, hence minzoom rather than a filter — which is also free at render time
        // and correctly releases collision space.
        ['large_airport', 'medium_airport', 'small_airport'].forEach((airportType) => {
            map.addLayer(labelSpec({
                id: `airports-label-${airportType.replace('_airport', '')}`,
                source: SOURCE,
                filter: ['all', NOT_A_CLUSTER, ['==', ['get', 'type'], airportType]],
                minzoom: LABEL_MINZOOM[airportType](),
            }));
        });

        // The focused and primary airports keep their label at every zoom, and never lose a
        // collision against a neighbour.
        map.addLayer(labelSpec({
            id: 'airports-label-pinned',
            source: SOURCE,
            filter: ['all', NOT_A_CLUSTER, ['in', ['get', 'icao'], ['literal', []]]],
            overlap: true,
        }));

        return () => {
            [...AIRPORT_LAYERS, 'airports-clusters', 'airports-cluster-count', 'airports-dots']
                .forEach((id) => { if (map.getLayer(id)) { map.removeLayer(id); } });

            if (map.getSource(SOURCE)) { map.removeSource(SOURCE); }
        };
    }, [map, cluster, clusterRadius, palette]);

    // Data updates are a plain setData — no teardown, no re-tiling of the basemap.
    useEffect(() => {
        map.getSource(SOURCE)?.setData(airports ? airportsToGeoJson(airports, palette) : EMPTY_FEATURE_COLLECTION);
    }, [map, airports, palette]);

    // Focus/primary highlighting is a paint update plus one filter swap: main-thread only, no
    // worker round-trip. feature-state would be unreliable here because Supercluster
    // regenerates its features per zoom and drops the state with them.
    useEffect(() => {
        const pinned = [focusAirport, primaryAirport].filter(Boolean);
        const color = ['case', ['==', ['get', 'icao'], focusAirport ?? ''], palette.fallback,
            ['to-color', ['get', 'color']]];

        if (map.getLayer('airports-dots')) {
            map.setPaintProperty('airports-dots', 'circle-color', color);
        }

        AIRPORT_LAYERS.filter((id) => id.startsWith('airports-label')).forEach((id) => {
            if (map.getLayer(id)) { map.setPaintProperty(id, 'text-color', color); }
        });

        if (map.getLayer('airports-label-pinned')) {
            map.setFilter('airports-label-pinned',
                ['all', NOT_A_CLUSTER, ['in', ['get', 'icao'], ['literal', pinned]]]);
        }
    }, [map, focusAirport, primaryAirport, palette]);

    useEffect(() => {
        const focus = (e) => setFocusAirport(e.features[0].properties.icao);

        const expandCluster = async (e) => {
            const feature = e.features[0];
            const zoom = await map.getSource(SOURCE).getClusterExpansionZoom(feature.properties.cluster_id);
            map.easeTo({ center: feature.geometry.coordinates, zoom });
        };

        const pointer = () => { map.getCanvas().style.cursor = 'pointer'; };
        const reset = () => { map.getCanvas().style.cursor = ''; };
        const interactive = [...AIRPORT_LAYERS, 'airports-clusters'];

        map.on('click', AIRPORT_LAYERS, focus);
        map.on('click', 'airports-clusters', expandCluster);
        map.on('mouseenter', interactive, pointer);
        map.on('mouseleave', interactive, reset);

        return () => {
            map.off('click', AIRPORT_LAYERS, focus);
            map.off('click', 'airports-clusters', expandCluster);
            map.off('mouseenter', interactive, pointer);
            map.off('mouseleave', interactive, reset);
        };
    }, [map, setFocusAirport]);

    return null;
};

export default MapAirportLayers;
