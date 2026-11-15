import React from 'react';
import ReactDOM from 'react-dom/client';
import { ErrorBoundary } from '@sentry/react';

import MapView, { MapFallback } from './map/MapView';
import { SCENE_DEFAULTS } from './map/mapScene';
import { useMapState } from './map/useMapState';

// The map on the Blade pages that still show one (login, admin, scenery editing). All of them show
// the default view, so nothing drives it; every other page lives in the Inertia shell.
function BladeMap() {
    const [state, actions] = useMapState(SCENE_DEFAULTS);

    return <MapView state={state} actions={actions} />;
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
