import { useContext, useEffect } from 'react';
import * as maplibregl from 'maplibre-gl';

import { MapContext } from '../context/MapContext';
import { useMapGL } from '../context/MapGLContext';

// One-shot radar blip at the given airport, triggered via window.pingAirport()
const MapPing = ({ ping }) => {

    const map = useMapGL();
    const { airports } = useContext(MapContext);

    useEffect(() => {
        const airport = ping ? airports[ping.icao] : null;

        if (!airport) {
            return undefined;
        }

        // A zero-size element whose ::before draws the expanding ring, exactly as the Leaflet
        // DivIcon did — .maplibregl-marker is position:absolute, so the CSS carries over.
        const element = document.createElement('div');
        element.className = 'radar-ping';

        const marker = new maplibregl.Marker({ element, anchor: 'center' })
            .setLngLat([Number(airport.lon), Number(airport.lat)])
            .addTo(map);

        const timer = setTimeout(() => marker.remove(), 1800);

        return () => {
            clearTimeout(timer);
            marker.remove();
        };
    }, [map, ping]);

    return null;
};

export default MapPing;
