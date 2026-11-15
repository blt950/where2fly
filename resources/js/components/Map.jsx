import React, { useEffect, useState } from 'react';
import ReactDOM from 'react-dom/client';
import { ErrorBoundary } from '@sentry/react';

import MapView, { MapFallback } from './map/MapView';
import { sceneFromRoute } from './map/mapScene';
import { useMapState } from './map/useMapState';

const notify = (name, detail) => window.dispatchEvent(new CustomEvent(name, { detail }));
const onFocusAirport = (focusAirport) => notify('mapFocusAirport', { focusAirport });
const onAirportNotFound = (icao) => notify('mapAirportNotFound', { icao });

// Temporary adapter for the pages still rendered by Blade: the scene comes from the URL and the
// inline page scripts drive it through window setters. Goes once the last map page is on Inertia.
function BladeMap() {

    const [scene] = useState(() => sceneFromRoute(route()));
    const [state, actions] = useMapState(scene, 'blade');

    useEffect(() => {
        const { set } = actions;

        window.setAirportsData = (airports) => set({ airports });
        window.setCluster = (cluster) => set({ cluster });
        window.setDrawRoute = (drawRoute) => set({ drawRoute });
        window.setFocusAirport = actions.setFocusAirport;
        window.pingAirport = actions.pingAirport;
        window.setHighlightedAircrafts = (highlightedAircrafts) => set({ highlightedAircrafts });
        window.setPrimaryAirport = (primaryAirport) => set({ primaryAirport });
        window.setReverseDirection = (reverseDirection) => set({ reverseDirection });

        window.dispatchEvent(new Event('mapReady'));
    }, [actions]);

    return (
        <MapView state={state} actions={actions} sceneKey="blade"
            onFocusAirport={onFocusAirport} onAirportNotFound={onAirportNotFound} />
    );
}

const mapElement = document.getElementById('map');
if (mapElement) {
    const root = ReactDOM.createRoot(mapElement);
    root.render(
        <ErrorBoundary fallback={<MapFallback />}>
            <BladeMap />
        </ErrorBoundary>
    );
}
