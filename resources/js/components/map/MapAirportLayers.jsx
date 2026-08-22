import { useContext, useEffect } from 'react';

import { MapContext } from '../context/MapContext';
import { useMapGL } from '../context/MapGLContext';
import { clusterSpecs, LABEL_MINZOOM, labelSpec, NOT_A_CLUSTER } from '../utils/airportLayerSpec';
import { airportsToGeoJson, EMPTY_FEATURE_COLLECTION } from '../utils/airportsGeoJson';

const SOURCE = 'airports';

const AIRPORT_LAYERS = ['airports-hit', 'airports-label-large', 'airports-label-medium',
    'airports-label-small', 'airports-label-pinned'];

const MapAirportLayers = ({ cluster, clusterRadius, palette }) => {

    const map = useMapGL();
    const { airports, focusAirport, primaryAirport, setFocusAirport } = useContext(MapContext);

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
            clusterSpecs({
                idPrefix: 'airports',
                source: SOURCE,
                color: isDefaultView() ? '#2f3549' : '#ddb81c',
                textColor: isDefaultView() ? '#ffffff' : '#000000',
            }).forEach((spec) => map.addLayer(spec));
        }

        map.addLayer({
            id: 'airports-dots',
            type: 'circle',
            source: SOURCE,
            filter: NOT_A_CLUSTER,
            paint: { 'circle-radius': ['get', 'r'], 'circle-color': ['to-color', ['get', 'color']] },
        });

        ['large_airport', 'medium_airport', 'small_airport'].forEach((airportType) => {
            map.addLayer(labelSpec({
                id: `airports-label-${airportType.replace('_airport', '')}`,
                source: SOURCE,
                filter: ['all', NOT_A_CLUSTER, ['==', ['get', 'type'], airportType]],
                minzoom: LABEL_MINZOOM[airportType](),
            }));
        });

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

    useEffect(() => {
        map.getSource(SOURCE)?.setData(airports ? airportsToGeoJson(airports, palette) : EMPTY_FEATURE_COLLECTION);
    }, [map, airports, palette]);

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
