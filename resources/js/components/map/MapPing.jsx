import { useContext, useEffect } from 'react';
import * as maplibregl from 'maplibre-gl';

import { MapContext } from '../context/MapContext';
import { useMapGL } from '../context/MapGLContext';

const MapPing = ({ ping }) => {

    const map = useMapGL();
    const { findAirport } = useContext(MapContext);

    useEffect(() => {
        const airport = ping ? findAirport(ping.icao) : null;

        if (!airport) {
            return undefined;
        }

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
