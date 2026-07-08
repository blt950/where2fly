import { useContext, useEffect } from 'react';
import L from 'leaflet';
import { useMap } from 'react-leaflet';

import { MapContext } from '../context/MapContext';

// One-shot radar blip at the given airport, triggered via window.pingAirport()
const MapPing = ({ ping }) => {
    const map = useMap();
    const { airports } = useContext(MapContext);

    useEffect(() => {
        const airport = ping ? airports[ping.icao] : null;
        if (!airport) {
            return;
        }

        const marker = L.marker([airport.lat, airport.lon], {
            icon: L.divIcon({ className: 'radar-ping', iconSize: [0, 0] }),
            interactive: false,
            keyboard: false,
        }).addTo(map);

        const timer = setTimeout(() => marker.remove(), 1800);

        return () => {
            clearTimeout(timer);
            marker.remove();
        };
    }, [ping]);

    return null;
};

export default MapPing;
