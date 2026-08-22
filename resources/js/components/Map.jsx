
import React, { useState, useEffect, useMemo } from 'react';
import ReactDOM from 'react-dom/client';
import { captureException, ErrorBoundary } from '@sentry/react';

import 'maplibre-gl/dist/maplibre-gl.css';

import { MapContext } from './context/MapContext';

import PopupContainer from './PopupContainer';
import MapAirportLayers from './map/MapAirportLayers';
import MapAttribution from './map/MapAttribution';
import MapControls from './map/MapControls';
import MapBound from './map/MapBound';
import MapPan from './map/MapPan';
import MapPing from './map/MapPing';
import MapProvider from './map/MapProvider';
import MapRoute from './map/MapRoute';
import MapSaveView from './map/MapSaveView';
import MapTerrain from './map/MapTerrain';
import MapUserList from './map/MapUserList';
import MapWeather from './map/MapWeather';
import MapTerminator from './map/MapTerminator';
import { themeOf } from './map/mapConfig';
import { readPreferences, writePreferences } from './utils/mapPreferences';

const userAuthenticated = document.querySelector('meta[name="user-authenticated"]')?.content === '1';

// MapLibre needs WebGL2. A device without it fails asynchronously inside the GL constructor,
// which the ErrorBoundary cannot catch, so probe up front and show the fallback directly
// rather than filling Sentry with unactionable errors from hardware we cannot fix.
const supportsWebGL2 = () => {
    try {
        return !!document.createElement('canvas').getContext('webgl2');
    } catch {
        return false;
    }
};

// Check if the current route is the default view
const isDefaultView = () => {
    if (!route().current('top') 
        && !route().current('top.filtered')
        && !route().current('search')
        && !route().current('search.routes')
        && !route().current('scenery')
        && !route().current('scenery.filtered')
        && route().current() !== undefined) {
        return true;
    }
    return false
}

// Get the initial map position. MapLibre takes [lng, lat] — the opposite order to Leaflet.
const getInitMapPosition = () => {

    // Set position based on current top list filter
    if(route().current('top.filtered', 'AF')){
        return [21.0936, 7.1881];
    } else if(route().current('top.filtered', 'AS')){
        return [100.6197, 34.0479];
    } else if(route().current('top.filtered', 'EU')){
        return [15.2551, 54.5260];
    } else if(route().current('top.filtered', 'NA')){
        return [-95.7129, 37.0902];
    } else if(route().current('top.filtered', 'OC')){
        return [133.7751, -25.2744];
    } else if(route().current('top.filtered', 'SA')){
        return [-55.4915, -8.7832];
    } else if(route().current('top')){
        // A place in the middle of the ocean to avoid stretching the map bounds
        return [-35.4521, 45.14777]
    }

    // Set position based on localStorage. The stored {lat, lng} shape predates MapLibre and
    // is kept as-is so existing visitors keep their saved view.
    var storedPosition = localStorage.getItem('mapPosition');
    if (storedPosition) {
        const { lat, lng } = JSON.parse(storedPosition);
        return [lng, lat];
    }

    // Default to Berlin
    return [13.395199187248908, 52.51843039016386];
}

function Map() {

    const [airports, setAirports] = useState([]);
    const [cluster, setCluster] = useState(true);
    const [initialCenter] = useState(getInitMapPosition);
    const [webgl2] = useState(supportsWebGL2);
    const [preferences, setPreferences] = useState(readPreferences);
    const [weatherStatus, setWeatherStatus] = useState('loading');
    const [lists, setLists] = useState([]);
    const [clusterRadius, setClusterRadius] = useState(null);
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
        window.isDefaultView = isDefaultView;

        // Seed the overlay from cache so the lists are on screen before the fetch returns.
        // The pre-grouping cache key is dropped rather than parsed — its shape no longer fits.
        localStorage.removeItem('userListAirportsCache');
        const cachedLists = localStorage.getItem('userListsCache');
        if (userAuthenticated && isDefaultView() && cachedLists) {
            try {
                setLists(JSON.parse(cachedLists) ?? []);
            } catch {
                localStorage.removeItem('userListsCache');
            }
        }

        // Dispatch a custom event when the map is ready
        window.dispatchEvent(new Event('mapReady'));

    }, []);

    // The user's scenery lists. Confined to the default view, as before the MapLibre migration:
    // on a search or top list the page's own airports are the subject, and the list would only
    // add noise. Fetched once; per-list visibility is applied locally.
    useEffect(() => {
        if (!userAuthenticated || !isDefaultView()) {
            localStorage.removeItem('userListsCache');
            setLists([]);

            return;
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

        if (!isDefaultView() && airports && airportsKeys.length > 0) {
            setMapBounds(Object.values(airports).map(airport => [airport.lon, airport.lat]));
        }
        
        if(airportsKeys.length > 0){
            if (airportsKeys.length >= 1000) {
                setClusterRadius(60);
            } else if(airportsKeys.length > 200 && airportsKeys.length < 1000) {
                setClusterRadius(50);
            } else {
                setClusterRadius(30);
            }
        }

    }, [airports]);

    const { palette } = themeOf(preferences.theme);

    // One source for every visible list — the airports already carry their list's colour, so
    // merging keeps a single set of layers instead of one per list.
    const listAirports = useMemo(() => Object.assign(
        {},
        ...lists.filter(({ id }) => preferences.lists?.[id] !== false).map(({ airports }) => airports),
    ), [lists, preferences.lists]);

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

    return (
        <MapContext.Provider value={mapContextValue}>
            <MapProvider center={initialCenter} projection={preferences.projection} theme={preferences.theme}>
                {preferences.terminator && <MapTerminator />}
                {preferences.terrain && <MapTerrain />}
                {preferences.weather && <MapWeather onStatus={setWeatherStatus} />}
                <MapAirportLayers cluster={cluster} clusterRadius={clusterRadius ?? 30} palette={palette} />
                {lists.length > 0 && <MapUserList listAirports={listAirports} palette={palette} />}
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

// #map is an empty <aside>: the .map class that gives it size lives on MapContainer, so the
// fallback has to carry it too or it collapses to nothing when the tree never renders.
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
