
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
import MapSaveView, { POSITION_KEY } from './map/MapSaveView';
import MapTerrain from './map/MapTerrain';
import MapWeather from './map/MapWeather';
import MapTerminator from './map/MapTerminator';
import { CLUSTER_COLOURS, themeOf } from './map/mapConfig';
import { AIRPORT_SOURCES } from './utils/airportLayerSpec';
import { isDefaultView } from './utils/mapRoutes';
import { readPreferences, writePreferences } from './utils/mapPreferences';
import { readStored, removeStored, writeStored } from './utils/storage';

const userAuthenticated = document.querySelector('meta[name="user-authenticated"]')?.content === '1';

// MapLibre needs WebGL2
const supportsWebGL2 = () => {
    try {
        return !!document.createElement('canvas').getContext('webgl2');
    } catch {
        return false;
    }
};

const DEFAULT_ZOOM = 4;
const LISTS_CACHE_KEY = 'userListsCache';

const postJson = (url, body) => fetch(url, {
    method: 'POST',
    credentials: 'include',
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
    },
    body: JSON.stringify(body),
});

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

    // Pre-MapLibre this was stored without a zoom, which view()'s default parameter covers.
    const stored = readStored(POSITION_KEY);

    if (Number.isFinite(stored?.lat) && Number.isFinite(stored?.lng)) {
        return view([stored.lng, stored.lat], stored.zoom);
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
            removeStored(LISTS_CACHE_KEY);

            return;
        }

        // Seed the overlay from cache so the lists are on screen before the fetch returns.
        setLists(readStored(LISTS_CACHE_KEY) ?? []);

        fetch(route('api.lists.airports'), { credentials: 'include', headers: { 'Accept': 'application/json' } })
            .then(response => response.json())
            .then(data => {
                writeStored(LISTS_CACHE_KEY, data.data);
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
                
                postJson(route('api.mapdata.icao'), { icao: focusAirport })
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

                    // Functional update: this lands after an await, so spreading the captured
                    // `airports` would revert anything written since (e.g. setAirportsData).
                    setAirports((previous) => ({ ...previous, [focusAirport]: airport }));
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
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [focusAirport]);

    // When airports data change, set the map bounds
    useEffect(() => {
        if (isDefaultView() || Object.keys(airports).length === 0) {
            return;
        }

        setMapBounds(Object.values(airports).map(airport => [airport.lon, airport.lat]));
    }, [airports]);

    const { palette, hillshade } = themeOf(preferences.theme);

    // One source for every visible list — the airports already carry their list's color, so
    // merging keeps a single set of layers instead of one per list.
    const listAirports = useMemo(() => Object.assign(
        {},
        ...lists.filter(({ hidden }) => !hidden).map(({ airports }) => airports),
    ), [lists]);

    // Scenery lists are held apart from `airports` so each one keeps its own color and toggle,
    // but any ICAO the user can click has to resolve from either — `airports` alone is what the
    // search-result layer draws, not the full set of focusable airports.
    const findAirport = useMemo(
        () => (icao) => airports[icao] ?? listAirports[icao],
        [airports, listAirports],
    );

    // `hidden` is one flag shared with the lists page, so the toggle has to reach the server.
    // Applied optimistically — the map redraws now and rolls back if the write fails.
    const setListHidden = (id, hidden) => {
        const previous = lists;
        const next = lists.map((list) => (list.id === id ? { ...list, hidden } : list));

        setLists(next);
        writeStored(LISTS_CACHE_KEY, next);

        postJson(route('api.lists.visibility', { list: id }), { hidden })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`Could not save list visibility (${response.status})`);
                }
            })
            .catch((error) => {
                setLists(previous);
                writeStored(LISTS_CACHE_KEY, previous);
                captureException(error);
                console.error(error.message);
            });
    };

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
        return <MapFallback webgl />;
    }

    const clusterColours = isDefaultView() ? CLUSTER_COLOURS.muted : CLUSTER_COLOURS.search;

    return (
        <MapContext.Provider value={mapContextValue}>
            <MapProvider view={initialView} projection={preferences.projection} theme={preferences.theme}>
                {preferences.terminator && <MapTerminator />}
                {preferences.terrain && <MapTerrain hillshade={hillshade} />}
                {preferences.weather && <MapWeather onStatus={setWeatherStatus} />}
                <MapAirportSource id={AIRPORT_SOURCES.results} airports={airports} palette={palette}
                    cluster={cluster} {...clusterColours} />
                {lists.length > 0 && (
                    <MapAirportSource id={AIRPORT_SOURCES.userLists} airports={listAirports} palette={palette}
                        cluster {...CLUSTER_COLOURS.muted} />
                )}
                {(mapBounds && !route().current('top*') && !route().current('scenery*')) && <MapBound mapBounds={mapBounds} />}
                {drawRoute && <MapRoute departure={drawRoute[0]} arrival={drawRoute[1]} reverseDirection={reverseDirection} color={palette.fallback} />}
                {!drawRoute && <MapPan flyToCoordinates={coordinates} />}
                {(isDefaultView() || route().current('scenery*')) && <MapSaveView />}
                <MapPing ping={ping} />
                <MapAttribution />
            </MapProvider>
            <MapControls preferences={preferences} onChange={updatePreferences} weatherStatus={weatherStatus}
                lists={lists} onListToggle={setListHidden} />
            {showAirportIdCard && <PopupContainer airportId={showAirportIdCard} />}
        </MapContext.Provider>
    );
}

export default Map;

// A missing WebGL2 context is the user's to turn back on, so don't tell them we were notified
// and to reload — neither is true.
function MapFallback({ webgl }) {
    return (
        <div className="map map-error d-flex flex-column align-items-center justify-content-center text-center p-4">
            <p className="mb-1">
                <i className="fa-sharp fa-triangle-exclamation" aria-hidden="true"></i> The map could not be loaded
            </p>
            <p className="mb-3">
                {webgl
                    ? 'This browser has no WebGL2, which the map needs to draw. It is usually disabled in the browser settings, or hardware acceleration is off.'
                    : 'We have been notified about it. Reloading the page usually sorts it.'}
            </p>
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
