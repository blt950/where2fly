import React from 'react';
import ReactDOM from 'react-dom/client';
import { ErrorBoundary } from '@sentry/react';

import MapView, { MapFallback } from './map/MapView';
import { SCENE_DEFAULTS } from './map/mapScene';
import { useMapState } from './map/useMapState';

// Temporary mount for the Blade pages that still show the map (the search forms and account
// pages). All of them show the default view, so nothing drives it. Goes with M4.
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
