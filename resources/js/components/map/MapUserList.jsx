import { useContext, useEffect } from 'react';

import { MapContext } from '../context/MapContext';
import { useMapGL } from '../context/MapGLContext';
import { clusterSpecs, focusColor, LABEL_MINZOOM, labelSpec, NOT_A_CLUSTER } from '../utils/airportLayerSpec';
import { airportsToGeoJson } from '../utils/airportsGeoJson';

const SOURCE = 'user-list';
const HIT = 'user-list-hit';
const DOTS = 'user-list-dots';
const CLUSTERS = 'user-list-clusters';
const CLUSTER_COUNT = 'user-list-cluster-count';

const LABELS = ['large_airport', 'medium_airport', 'small_airport']
    .map((airportType) => [`user-list-label-${airportType.replace('_airport', '')}`, airportType]);

const CLICKABLE = [HIT, ...LABELS.map(([id]) => id)];

const MapUserList = ({ listAirports, clusterRadius, palette }) => {

    const map = useMapGL();
    const { focusAirport, setFocusAirport } = useContext(MapContext);

    useEffect(() => {
        map.addSource(SOURCE, {
            type: 'geojson',
            data: airportsToGeoJson(listAirports, palette),
            cluster: true,
            clusterRadius,
            clusterMaxZoom: 12,
        });

        // Same 9px target as the search airports — a small_airport dot is 5px across.
        map.addLayer({
            id: HIT,
            type: 'circle',
            source: SOURCE,
            filter: NOT_A_CLUSTER,
            paint: { 'circle-radius': 9, 'circle-opacity': 0 },
        });

        // The home page's own cluster colours: a cluster can span several lists, so it carries
        // no list colour of its own — that returns as soon as it breaks apart.
        clusterSpecs({ idPrefix: 'user-list', source: SOURCE, color: '#2f3549', textColor: '#ffffff' })
            .forEach((spec) => map.addLayer(spec));

        map.addLayer({
            id: DOTS,
            type: 'circle',
            source: SOURCE,
            filter: NOT_A_CLUSTER,
            paint: { 'circle-radius': ['get', 'r'], 'circle-color': ['to-color', ['get', 'color']] },
        });

        LABELS.forEach(([id, airportType]) => {
            map.addLayer(labelSpec({
                id,
                source: SOURCE,
                filter: ['all', NOT_A_CLUSTER, ['==', ['get', 'type'], airportType]],
                minzoom: LABEL_MINZOOM[airportType](),
            }));
        });

        const focus = (event) => setFocusAirport(event.features[0].properties.icao);

        const expandCluster = async (event) => {
            const feature = event.features[0];
            const zoom = await map.getSource(SOURCE).getClusterExpansionZoom(feature.properties.cluster_id);
            map.easeTo({ center: feature.geometry.coordinates, zoom });
        };

        const pointer = () => { map.getCanvas().style.cursor = 'pointer'; };
        const reset = () => { map.getCanvas().style.cursor = ''; };
        const interactive = [...CLICKABLE, CLUSTERS];

        map.on('click', CLICKABLE, focus);
        map.on('click', CLUSTERS, expandCluster);
        map.on('mouseenter', interactive, pointer);
        map.on('mouseleave', interactive, reset);

        return () => {
            map.off('click', CLICKABLE, focus);
            map.off('click', CLUSTERS, expandCluster);
            map.off('mouseenter', interactive, pointer);
            map.off('mouseleave', interactive, reset);

            [...CLICKABLE, DOTS, CLUSTERS, CLUSTER_COUNT]
                .forEach((id) => { if (map.getLayer(id)) { map.removeLayer(id); } });

            if (map.getSource(SOURCE)) { map.removeSource(SOURCE); }
        };
    }, [map, clusterRadius, palette, setFocusAirport]);

    useEffect(() => {
        map.getSource(SOURCE)?.setData(airportsToGeoJson(listAirports, palette));
    }, [map, listAirports, palette]);

    // Same focus highlight the search airports get — a list airport is clickable, so it has to
    // show which one is open.
    useEffect(() => {
        const color = focusColor(focusAirport, palette);

        if (map.getLayer(DOTS)) { map.setPaintProperty(DOTS, 'circle-color', color); }

        LABELS.forEach(([id]) => {
            if (map.getLayer(id)) { map.setPaintProperty(id, 'text-color', color); }
        });
    }, [map, focusAirport, palette]);

    return null;
};

export default MapUserList;
