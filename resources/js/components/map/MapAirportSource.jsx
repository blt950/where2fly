import { useContext, useEffect } from 'react';

import { MapContext } from '../context/MapContext';
import { useMapGL } from '../context/MapGLContext';
import { clusterIds, clusterSpecs, focusColor, hitId, labelIds, labelMinzoom, labelSpec, labelTypeFilter, NOT_A_CLUSTER } from '../utils/airportLayerSpec';
import { airportsToGeoJson } from '../utils/airportsGeoJson';

const pinnedFilter = (icaos) => ['all', NOT_A_CLUSTER, ['in', ['get', 'icao'], ['literal', icaos]]];

// One clustered GeoJSON source and its hit/cluster/dot/label layers. Both the search results and
// the user's own lists render through this, so they cluster and label identically.
const MapAirportSource = ({ id, airports, palette, cluster, clusterColor, clusterTextColor }) => {

    const map = useMapGL();
    const { focusAirport, primaryAirport, setFocusAirport } = useContext(MapContext);

    const labels = labelIds(id);
    const clusters = clusterIds(id);
    const hit = hitId(id);
    const pinned = `${id}-label-pinned`;
    const dots = `${id}-dots`;

    useEffect(() => {
        map.addSource(id, {
            type: 'geojson',
            data: airportsToGeoJson(airports, palette),
            cluster,
            clusterMaxZoom: 12,
        });

        // A small_airport is a 5px click target. A transparent circle over it fixes that;
        // queryRenderedFeatures tests geometry, not alpha, so the clicks still land.
        map.addLayer({
            id: hit,
            type: 'circle',
            source: id,
            filter: NOT_A_CLUSTER,
            paint: { 'circle-radius': 9, 'circle-opacity': 0 },
        });

        if (cluster) {
            clusterSpecs({ idPrefix: id, source: id, color: clusterColor, textColor: clusterTextColor })
                .forEach((spec) => map.addLayer(spec));
        }

        map.addLayer({
            id: dots,
            type: 'circle',
            source: id,
            filter: NOT_A_CLUSTER,
            paint: { 'circle-radius': ['get', 'r'], 'circle-color': ['to-color', ['get', 'color']] },
        });

        labels.forEach(([labelId, airportType]) => map.addLayer(labelSpec({
            id: labelId,
            source: id,
            filter: ['all', NOT_A_CLUSTER, labelTypeFilter(airportType)],
            minzoom: labelMinzoom(airportType),
        })));

        // The focused and primary airports keep their label at every zoom.
        map.addLayer(labelSpec({ id: pinned, source: id, filter: pinnedFilter([]), overlap: true }));

        return () => {
            [hit, ...clusters, dots, ...labels.map(([labelId]) => labelId), pinned]
                .forEach((layer) => { if (map.getLayer(layer)) { map.removeLayer(layer); } });

            if (map.getSource(id)) { map.removeSource(id); }
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [map, id, cluster, clusterColor, clusterTextColor, palette]);

    useEffect(() => {
        map.getSource(id)?.setData(airportsToGeoJson(airports, palette));
    }, [map, id, airports, palette]);

    useEffect(() => {
        const color = focusColor(focusAirport, palette);

        [dots, ...labels.map(([labelId]) => labelId), pinned].forEach((layer) => {
            if (map.getLayer(layer)) {
                map.setPaintProperty(layer, layer === dots ? 'circle-color' : 'text-color', color);
            }
        });

        if (map.getLayer(pinned)) {
            map.setFilter(pinned, pinnedFilter([focusAirport, primaryAirport].filter(Boolean)));
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [map, id, focusAirport, primaryAirport, palette]);

    useEffect(() => {
        // Topmost feature wins, so a cluster drawn over a loose airport expands rather than focuses.
        const onClick = async (event) => {
            const { properties, geometry } = event.features[0];

            if (properties.cluster_id === undefined) {
                setFocusAirport(properties.icao);

                return;
            }

            const zoom = await map.getSource(id).getClusterExpansionZoom(properties.cluster_id);
            map.easeTo({ center: geometry.coordinates, zoom });
        };

        const pointer = () => { map.getCanvas().style.cursor = 'pointer'; };
        const reset = () => { map.getCanvas().style.cursor = ''; };
        const interactive = [hit, clusters[0], ...labels.map(([labelId]) => labelId), pinned];

        map.on('click', interactive, onClick);
        map.on('mouseenter', interactive, pointer);
        map.on('mouseleave', interactive, reset);

        return () => {
            map.off('click', interactive, onClick);
            map.off('mouseenter', interactive, pointer);
            map.off('mouseleave', interactive, reset);
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [map, id, setFocusAirport]);

    return null;
};

export default MapAirportSource;
