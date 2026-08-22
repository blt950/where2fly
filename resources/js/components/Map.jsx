
import React, { useState, useEffect, useMemo } from 'react';
import ReactDOM from 'react-dom/client';
import { captureException, ErrorBoundary } from '@sentry/react';

import 'maplibre-gl/dist/maplibre-gl.css';

import { MapContext } from './context/MapContext';

import PopupContainer from './PopupContainer';
import MapAirportSource from './map/MapAirportSource';
import MapAttribution from './map/MapAttribution';
import MapControls from './map/MapControls';
import MapBound from './map/MapBound';
import MapPan from './map/MapPan';
import MapPing from './map/MapPing';
import MapProvider from './map/MapProvider';
import MapRoute from './map/MapRoute';
import MapSaveView from './map/MapSaveView';
import MapTerrain from './map/MapTerrain';
import MapWeather from './map/MapWeather';
import MapTerminator from './map/MapTerminator';
import { CLUSTER_COLOURS, themeOf } from './map/mapConfig';
import { isDefaultView } from './utils/mapRoutes';
import { readPreferences, writePreferences } from './utils/mapPreferences';

const userAuthenticated = document.querySelector('meta[name="user-authenticated"]')?.content === '1';

// MapLibre needs WebGL2
const supportsWebGL2 = () => {
    try {
        return !!document.createElement('canvas').getContext('webgl2');
    } catch {
        return false;
    }
};

// A denser map needs a wider merge radius, or the clusters themselves become the clutter.
const clusterRadiusFor = (count) => (count >= 1000 ? 60 : count > 200 ? 50 : 30);

const DEFAULT_ZOOM = 4;

// MapLibre takes [lng, lat] — the opposite order to Leaflet. Zoom is optional per entry and
// falls back to DEFAULT_ZOOM, so only the odd ones out need to say it.
const view = (center, zoom = DEFAULT_ZOOM) => ({ center, zoom });

const CONTINENT_VIEWS = {
    AF: view([21, 0], 3.2),
    AS: view([100, 35], 3),
    EU: view([15, 55]),
    NA: view([-95, 45]),
    OC: view([135, -25], 3.5),
    SA: view([-55, -20], 3),
};

const getInitMapPosition = () => {

    const continent = Object.keys(CONTINENT_VIEWS).find((code) => route().current('top.filtered', code));

    if (continent) {
        return CONTINENT_VIEWS[continent];
    }

    if (route().current('top')) {
        return view([-0, 30], 2);
    }

    const storedPosition = localStorage.getItem('mapPosition');

    if (storedPosition) {
        const { lat, lng, zoom } = JSON.parse(storedPosition);

        return view([lng, lat], zoom);
    }

    // Default to Berlin
    return view([13.395199187248908, 52.51843039016386]);
}

function Map() {

    const [airports, setAirports] = useState({});
    const [cluster, setCluster] = useState(true);
    const [initialView] = useState(getInitMapPosition);
    const [webgl2] = useState(supportsWebGL2);
    const [preferences, setPreferences] = useState(readPreferences);
    const [weatherStatus, setWeatherStatus] = useState('loading');
    const [lists, setLists] = useState([]);
    const [clusterRadius, setClusterRadius] = useState(30);
    const [coordinates, setCoordinates] = useState(null);
    const [drawRoute, setDrawRoute] = useState(null);
    const [focusAirport, setFocusAirport] = useState(null);
    const [highlightedAircrafts, setHighlightedAircrafts] = useState([]);
    const [mapBounds, setMapBounds] = useState(null);
    const [ping, setPing] = useState(null);
    const [primaryAirport, setPrimaryAirport] = useState(null);
    const [reverseDirection, setReverseDirection] = useState(null);
    const [showAirportIdCard, setShowAirportIdCard] = useState(null);

    // On initial load
    useEffect(() => {
        window.setAirportsData = (data) => { setAirports(data) }
        window.setCluster = (boolean) => { setCluster(boolean) }
        window.setDrawRoute = (route) => { setDrawRoute(route) }
        window.setFocusAirport = (icao) => { setFocusAirport(icao) }
        window.pingAirport = (icao) => { setPing({ icao, ts: Date.now() }) }
        window.setHighlightedAircrafts = (data) => { setHighlightedAircrafts(data) }
        window.setPrimaryAirport = (airport) => { setPrimaryAirport(airport) }
        window.setReverseDirection = (boolean) => { setReverseDirection(boolean) }

        // Dispatch a custom event when the map is ready
        window.dispatchEvent(new Event('mapReady'));

        if (!userAuthenticated || !isDefaultView()) {
            localStorage.removeItem('userListsCache');

            return;
        }

        // Seed the overlay from cache so the lists are on screen before the fetch returns.
        try {
            setLists(JSON.parse(localStorage.getItem('userListsCache')) ?? []);
        } catch {
            localStorage.removeItem('userListsCache');
        }

        fetch(route('api.lists.airports'), { credentials: 'include', headers: { 'Accept': 'application/json' } })
            .then(response => response.json())
            .then(data => {
                localStorage.setItem('userListsCache', JSON.stringify(data.data));
                setLists(data.data ?? []);
            })
            .catch(error => {
                captureException(error);
                console.error(error.message);
            });

    }, []);

    // When focusAirport changes, pan to the airport and show the card.
    useEffect(() => {
        if (focusAirport !== null && focusAirport !== undefined) {

            // Load the selected airport to map if it's not already loaded (e.g. searching up scenery)
            const known = findAirport(focusAirport);

            if (known === undefined) {
                
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                fetch(route('api.mapdata.icao'), {
                    method: "POST",
                    credentials: 'include',
                    headers: { 
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ icao: focusAirport })
                })
                .then(response => response.json().then(body => ({ ok: response.ok, body })))
                .then(({ ok, body }) => {
                    const airport = ok ? body.data?.[focusAirport] : undefined;

                    // The scenery search box takes free text, so an unknown ICAO is a
                    // 422 without a data key — expected, and not worth reporting
                    if (airport === undefined) {
                        setFocusAirport(null);
                        window.dispatchEvent(new CustomEvent('mapAirportNotFound', { detail: { icao: focusAirport } }));
                        return;
                    }

                    setAirports({ ...airports, [focusAirport]: airport });
                    // Use the temporary data as setAirports is async
                    setCoordinates([airport.lon, airport.lat]);
                    setShowAirportIdCard(airport.id);
                })
                .catch(error => {
                    captureException(error);
                    console.error(error.message);
                });

            } else {

                // Set the coordinates and show the card
                setCoordinates([Number(known.lon), Number(known.lat)]);
                setShowAirportIdCard(known.id);

                // For routes which define a primary airport, we want to draw the route as well
                if(primaryAirport){
                    setDrawRoute([primaryAirport, known.icao]);
                }

                // Dispatch a custom event when the map focuses on an airport
                window.dispatchEvent(new CustomEvent('mapFocusAirport', { detail: { focusAirport } }));

            }

        } else if (primaryAirport) {
            setDrawRoute(null);
            setCoordinates(null);
        }
    }, [focusAirport]);

    // When airports data change, set the map bounds
    useEffect(() => {

        const airportsKeys = Object.keys(airports);

        if (airportsKeys.length === 0) {
            return;
        }

        if (!isDefaultView()) {
            setMapBounds(Object.values(airports).map(airport => [airport.lon, airport.lat]));
        }

        setClusterRadius(clusterRadiusFor(airportsKeys.length));

    }, [airports]);

    const { palette, hillshade } = themeOf(preferences.theme);

    // One source for every visible list — the airports already carry their list's colour, so
    // merging keeps a single set of layers instead of one per list.
    const listAirports = useMemo(() => Object.assign(
        {},
        ...lists.filter(({ id }) => preferences.lists?.[id] !== false).map(({ airports }) => airports),
    ), [lists, preferences.lists]);

    const listClusterRadius = useMemo(
        () => clusterRadiusFor(Object.keys(listAirports).length),
        [listAirports],
    );

    // Scenery lists are held apart from `airports` so each one keeps its own colour and toggle,
    // but any ICAO the user can click has to resolve from either — `airports` alone is what the
    // search-result layer draws, not the full set of focusable airports.
    const findAirport = useMemo(
        () => (icao) => airports[icao] ?? listAirports[icao],
        [airports, listAirports],
    );

    const updatePreferences = (next) => {
        setPreferences(next);
        writePreferences(next);
    };

    // Memoise the context value so unrelated Map state changes (coordinates,
    // mapBounds, showAirportIdCard, panning) don't give it a new identity and
    // re-render every marker. Only changes to the listed values propagate.
    const mapContextValue = useMemo(() => ({
        airports,
        setAirports,
        findAirport,
        focusAirport,
        highlightedAircrafts,
        primaryAirport,
        reverseDirection,
        setFocusAirport,
        setShowAirportIdCard,
        userAuthenticated,
    }), [airports, findAirport, focusAirport, highlightedAircrafts, primaryAirport, reverseDirection]);

    if (!webgl2) {
        return <MapFallback />;
    }

    const clusterColours = isDefaultView() ? CLUSTER_COLOURS.muted : CLUSTER_COLOURS.search;

    return (
        <MapContext.Provider value={mapContextValue}>
            <MapProvider view={initialView} projection={preferences.projection} theme={preferences.theme}>
                {preferences.terminator && <MapTerminator />}
                {preferences.terrain && <MapTerrain hillshade={hillshade} />}
                {preferences.weather && <MapWeather onStatus={setWeatherStatus} />}
                <MapAirportSource id="airports" airports={airports} palette={palette}
                    cluster={cluster} clusterRadius={clusterRadius} {...clusterColours} />
                {lists.length > 0 && (
                    <MapAirportSource id="user-list" airports={listAirports} palette={palette}
                        cluster clusterRadius={listClusterRadius} {...CLUSTER_COLOURS.muted} />
                )}
                {(mapBounds && !route().current('top*') && !route().current('scenery*')) && <MapBound mapBounds={mapBounds} />}
                {drawRoute && <MapRoute departure={drawRoute[0]} arrival={drawRoute[1]} reverseDirection={reverseDirection} color={palette.fallback} />}
                {!drawRoute && <MapPan flyToCoordinates={coordinates} />}
                {(isDefaultView() || route().current('scenery*')) && <MapSaveView />}
                <MapPing ping={ping} />
                <MapAttribution />
            </MapProvider>
            <MapControls preferences={preferences} onChange={updatePreferences} weatherStatus={weatherStatus} lists={lists} />
            {showAirportIdCard && <PopupContainer airportId={showAirportIdCard} />}
        </MapContext.Provider>
    );
}

export default Map;

function MapFallback() {
    return (
        <div className="map map-error d-flex flex-column align-items-center justify-content-center text-center p-4">
            <p className="mb-1">
                <i className="fa-sharp fa-triangle-exclamation" aria-hidden="true"></i> The map could not be loaded
            </p>
            <p className="mb-3">We have been notified about it. Reloading the page usually sorts it.</p>
            <button type="button" className="btn btn-primary" onClick={() => window.location.reload()}>
                Reload page
            </button>
        </div>
    );
}

const mapElement = document.getElementById('map');
if (mapElement) {
    const root = ReactDOM.createRoot(mapElement);
    root.render(
        <ErrorBoundary fallback={<MapFallback />}>
            <Map />
        </ErrorBoundary>
    );
}
