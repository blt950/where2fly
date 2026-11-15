import React, { useEffect, useMemo, useState } from 'react';
import { captureException, ErrorBoundary } from '@sentry/react';

import 'maplibre-gl/dist/maplibre-gl.css';

import { MapContext } from '../context/MapContext';
import { useMapLayout } from '../context/MapLayoutContext';

import CardHost from '../CardHost';
import MapAirportSource from './MapAirportSource';
import MapAttribution from './MapAttribution';
import MapBackgroundClick from './MapBackgroundClick';
import MapBound from './MapBound';
import MapCamera from './MapCamera';
import MapControls from './MapControls';
import MapFocusAirport from './MapFocusAirport';
import MapPan from './MapPan';
import MapPing from './MapPing';
import MapProvider from './MapProvider';
import MapRoute from './MapRoute';
import MapSaveView from './MapSaveView';
import MapTerminator from './MapTerminator';
import MapTerrain from './MapTerrain';
import MapWeather from './MapWeather';
import { CLUSTER_COLOURS, themeOf } from './mapConfig';
import { cameraView } from './mapScene';
import { AIRPORT_SOURCES } from '../utils/airportLayerSpec';
import { readPreferences, writePreferences } from '../utils/mapPreferences';
import { readStored, removeStored, writeStored } from '../utils/storage';
import { asList, mergeListAirports } from '../utils/userLists';

const userAuthenticated = () => document.querySelector('meta[name="user-authenticated"]')?.content === '1';

// MapLibre needs WebGL2
const supportsWebGL2 = () => {
    try {
        return !!document.createElement('canvas').getContext('webgl2');
    } catch {
        return false;
    }
};

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

const reportError = (error) => {
    captureException(error);
    console.error(error.message);
};

// Draws whatever `state` describes. The owner (the Blade adapter or the Inertia shell) holds the
// state, so pages can read and change focus without DOM events.
const pageLoad = (url) => window.location.assign(url);

function MapView({ state, actions, onFocusAirport, onAirportNotFound, onBackgroundClick, navigate = pageLoad }) {

    const {
        airports, cluster, clusterTone, camera: sceneCamera, coordinates, drawRoute, focusAirport, gateLabels,
        highlightedAircrafts, initialCard, mapBounds, persistView, ping, primaryAirport, reverseDirection,
        sceneVersion, showAirportIdCard, showLists,
    } = state;
    const { set, addAirport, setFocusAirport, setShowAirportIdCard } = actions;

    // A scene can ask to frame its airports instead on the compact layout, where its fixed view
    // (tuned for a wide map) would leave most of them under the drawer or off a narrow screen
    const { compact } = useMapLayout();
    const camera = compact && sceneCamera?.compact === 'fit' ? { type: 'fit' } : sceneCamera;

    const [initialView] = useState(() => cameraView(camera));
    const [webgl2] = useState(supportsWebGL2);
    const [preferences, setPreferences] = useState(readPreferences);
    const [weatherStatus, setWeatherStatus] = useState('loading');
    const [lists, setLists] = useState([]);

    useEffect(() => {
        if (!userAuthenticated() || !showLists) {
            removeStored(LISTS_CACHE_KEY);
            setLists([]);

            return undefined;
        }

        let cancelled = false;

        // Seed the overlay from cache so the lists are on screen before the fetch returns.
        setLists(asList(readStored(LISTS_CACHE_KEY)));

        fetch(route('api.lists.airports'), { credentials: 'include', headers: { 'Accept': 'application/json' } })
            .then(response => response.json())
            .then(data => {
                const fetched = asList(data.data);

                writeStored(LISTS_CACHE_KEY, fetched);

                if (!cancelled) {
                    setLists(fetched);
                }
            })
            .catch(reportError);

        return () => { cancelled = true; };
    }, [showLists]);

    const listAirports = useMemo(() => mergeListAirports(lists), [lists]);

    // Scenery lists are held apart from `airports` so each one keeps its own color and toggle,
    // but any ICAO the user can click has to resolve from either — `airports` alone is what the
    // search-result layer draws, not the full set of focusable airports.
    const findAirport = useMemo(
        () => (icao) => airports[icao] ?? listAirports[icao],
        [airports, listAirports],
    );

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
                            onAirportNotFound?.(focusAirport);
                            return;
                        }

                        addAirport(focusAirport, airport);
                        set({ coordinates: [airport.lon, airport.lat], showAirportIdCard: airport.id });
                    })
                    .catch(reportError);

            } else {

                set({
                    coordinates: [Number(known.lon), Number(known.lat)],
                    showAirportIdCard: known.id,
                    // For routes which define a primary airport, we want to draw the route as well
                    ...(primaryAirport ? { drawRoute: [primaryAirport, known.icao] } : {}),
                });

                onFocusAirport?.(focusAirport);
            }

        } else if (primaryAirport) {
            set({ drawRoute: null, coordinates: null });
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [focusAirport]);

    // When airports data change, frame them if the scene asks for it
    useEffect(() => {
        if (camera?.type !== 'fit' || Object.keys(airports).length === 0) {
            return;
        }

        set({ mapBounds: Object.values(airports).map(airport => [airport.lon, airport.lat]) });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [airports, camera?.type]);

    const { palette, hillshade } = themeOf(preferences.theme);
    const focusedAirport = focusAirport ? findAirport(focusAirport) : undefined;

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
                reportError(error);
            });
    };

    const updatePreferences = (next) => {
        setPreferences(next);
        writePreferences(next);
    };

    // Memoise the context value so unrelated state changes (coordinates, mapBounds,
    // showAirportIdCard, panning) don't give it a new identity and re-render every marker.
    const mapContextValue = useMemo(() => ({
        airports,
        findAirport,
        focusAirport,
        highlightedAircrafts,
        initialCard,
        navigate,
        primaryAirport,
        reverseDirection,
        setFocusAirport,
        setShowAirportIdCard,
        userAuthenticated: userAuthenticated(),
    }), [airports, findAirport, focusAirport, highlightedAircrafts, initialCard, navigate, primaryAirport, reverseDirection,
        setFocusAirport, setShowAirportIdCard]);

    if (!webgl2) {
        return <MapFallback webgl />;
    }

    const clusterColours = CLUSTER_COLOURS[clusterTone] ?? CLUSTER_COLOURS.muted;

    return (
        <MapContext.Provider value={mapContextValue}>
            <MapProvider view={initialView} projection={preferences.projection} theme={preferences.theme}>
                {preferences.terminator && <MapTerminator />}
                {preferences.terrain && <MapTerrain hillshade={hillshade} />}
                {preferences.weather && <MapWeather onStatus={setWeatherStatus} />}
                <MapAirportSource id={AIRPORT_SOURCES.results} airports={airports} palette={palette}
                    cluster={cluster} gateLabels={gateLabels} {...clusterColours} />
                {lists.length > 0 && (
                    <MapAirportSource id={AIRPORT_SOURCES.userLists} airports={listAirports} palette={palette}
                        cluster {...CLUSTER_COLOURS.muted} />
                )}
                <MapCamera camera={camera} sceneVersion={sceneVersion} />
                {(mapBounds && camera?.type === 'fit') && <MapBound mapBounds={mapBounds} />}
                {drawRoute && <MapRoute departure={drawRoute[0]} arrival={drawRoute[1]} reverseDirection={reverseDirection} color={palette.fallback} />}
                {!drawRoute && <MapPan flyToCoordinates={coordinates} />}
                {persistView && <MapSaveView />}
                {focusedAirport && <MapFocusAirport airport={focusedAirport} color={palette.fallback} />}
                <MapPing ping={ping} />
                <MapBackgroundClick onClick={onBackgroundClick} />
                <MapAttribution />
            </MapProvider>
            <MapControls preferences={preferences} onChange={updatePreferences} weatherStatus={weatherStatus}
                lists={lists} onListToggle={setListHidden} />
            {showAirportIdCard && (
                // A card that throws mid-commit used to hit the root boundary and take the whole
                // map down with it — the map stays up, only the card goes. Keyed on the scene so
                // the cards' per-airport caches can't outlive the results they were fetched for.
                <ErrorBoundary key={sceneVersion} fallback={null}>
                    <CardHost airportId={showAirportIdCard} />
                </ErrorBoundary>
            )}
        </MapContext.Provider>
    );
}

export default MapView;

// A missing WebGL2 context is the user's to turn back on, so don't tell them we were notified
// and to reload — neither is true.
export function MapFallback({ webgl }) {
    return (
        <div className="map map-error d-flex flex-column align-items-center justify-content-center text-center p-4">
            <p className="mb-1">
                <i className="fa-sharp fa-triangle-exclamation" aria-hidden="true"></i> The map could not be loaded
            </p>
            <p className="mb-3">
                {webgl
                    ? 'WebGL2 needed to draw the map. Check if hardware acceleration is on and WebGL2 is enabled in browser settings.'
                    : 'We have been notified about it. Try reloading the page.'}
            </p>
            <button type="button" className="btn btn-primary" onClick={() => window.location.reload()}>
                Reload page
            </button>
        </div>
    );
}
