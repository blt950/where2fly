
import React, { useState, useEffect } from 'react';
import ReactDOM from 'react-dom/client';

import { MapContext } from './context/MapContext';

import { createClusterIcon } from './utils/ClusterIcon';
import PopupContainer from './PopupContainer';
import MarkerClusterGroup from 'react-leaflet-cluster';
import { MapContainer, TileLayer } from 'react-leaflet'

import MapBound from './map/MapBound';
import MapDrawRoute from './map/MapDrawRoute';
import MapMarkerGroup from './map/MapMarkerGroup';
import MapPan from './map/MapPan';
import MapSaveView from './map/MapSaveView';

// Check if the current route is the default view
const isDefaultView = () => {
    if (!route().current('top') 
        && !route().current('top.filtered')
        && !route().current('search')
        && route().current() !== undefined) {
        return true;
    }
    return false
}

// Get the initial map position
const getInitMapPosition = () => {

    // Set position based on current top list filter
    if(route().current('top.filtered', 'AF')){
        return [7.1881, 21.0936];
    } else if(route().current('top.filtered', 'AS')){
        return [34.0479, 100.6197];
    } else if(route().current('top.filtered', 'EU')){
        return [54.5260, 15.2551];
    } else if(route().current('top.filtered', 'NA')){
        return [37.0902, -95.7129];
    } else if(route().current('top.filtered', 'OC')){
        return [-25.2744, 133.7751];
    } else if(route().current('top.filtered', 'SA')){
        return [-8.7832, -55.4915];
    }

    // Set position based on localStorage
    var storedPosition = localStorage.getItem('mapPosition');
    if (storedPosition) {
        const { lat, lng } = JSON.parse(storedPosition);
        return [lat, lng];
    }

    // Default to Berlin
    return [52.51843039016386, 13.395199187248908];
}

function Map() {

    const [airports, setAirports] = useState([]);
    const [cluster, setCluster] = useState(true);
    const [coordinates, setCoordinates] = useState(null);
    const [drawRoute, setDrawRoute] = useState(null);
    const [focusAirport, setFocusAirport] = useState(null);
    const [highlightedAircrafts, setHighlightedAircrafts] = useState([]);
    const [mapBounds, setMapBounds] = useState(null);
    const [primaryAirport, setPrimaryAirport] = useState(null);
    const [reverseDirection, setReverseDirection] = useState(null);
    const [showAirportIdCard, setShowAirportIdCard] = useState(null);
    const [userAuthenticated, setUserAuthenticated] = useState(false);

    // On initial load
    useEffect(() => {
        window.setAirportsData = (data) => { setAirports(data) }
        window.setCluster = (boolean) => { setCluster(boolean) }
        window.setDrawRoute = (route) => { setDrawRoute(route) }
        window.setFocusAirport = (icao) => { setFocusAirport(icao) }
        window.setHighlightedAircrafts = (data) => { setHighlightedAircrafts(data) }
        window.setPrimaryAirport = (airport) => { setPrimaryAirport(airport) }
        window.setReverseDirection = (boolean) => { setReverseDirection(boolean) }
        window.isDefaultView = isDefaultView;

        // Check if user is authenticated
        fetch(route('api.user.authenticated'), { credentials: 'include', headers: { 'Accept': 'application/json' } })
            .then(response => response.json())
            .then(data => setUserAuthenticated(data.data))
            .catch(error => console.error(error.message));
    
        // Dispatch a custom event when the map is ready
        window.dispatchEvent(new Event('mapReady'));

    }, []);

    // Fetch the user's list if they are authenticated
    useEffect(() => {
        if (isDefaultView() && userAuthenticated) {
            fetch(route('api.lists.airports'), { credentials: 'include', headers: { 'Accept': 'application/json' } })
                .then(response => response.json())
                .then(data => setAirports(data.data))
                .catch(error => console.error(error.message));
        }
    }, [userAuthenticated]);

    // When focusAirport changes, pan to the airport and show the card.
    useEffect(() => {
        if (focusAirport !== null && focusAirport !== undefined) {
            setCoordinates([airports[focusAirport].lat, airports[focusAirport].lon]);
            setShowAirportIdCard(airports[focusAirport].id);

            // For routes which define a primary airport, we want to draw the route as well
            if(primaryAirport){
                setDrawRoute([primaryAirport, airports[focusAirport].icao]);
            }

            // Dispatch a custom event when the map focuses on an airport
            window.dispatchEvent(new CustomEvent('mapFocusAirport', { detail: { focusAirport } }));
        }
    }, [focusAirport]);

    // When airports data change, set the map bounds
    useEffect(() => {
        if (!isDefaultView() && airports && Object.keys(airports).length > 0) {
            var bounds = [];
            Object.values(airports).forEach(airport => {
                bounds.push([airport.lat, airport.lon]);
            });
            setMapBounds(bounds);
        }
    }, [airports]);

    return (
        <MapContext.Provider value={{ 
            airports, 
            focusAirport, 
            highlightedAircrafts,
            primaryAirport,
            reverseDirection,
            setFocusAirport, 
            setShowAirportIdCard, 
            userAuthenticated, 
            }}>
            <MapContainer 
                className="map" 
                center={getInitMapPosition()}
                zoom={4} 
                attributionControl={false} 
                zoomControl={false}
                maxBounds={[[-85, -360], [85, 360]]}
            >
                <TileLayer
                    url="https://{s}.basemaps.cartocdn.com/dark_nolabels/{z}/{x}/{y}{r}.png"
                    minZoom={3}
                    maxZoom={17}
                />

                {cluster ? (
                    <MarkerClusterGroup showCoverageOnHover={false} maxClusterRadius={40} iconCreateFunction={createClusterIcon}>
                        <MapMarkerGroup/>
                    </MarkerClusterGroup>
                ) : (
                    <MapMarkerGroup/>
                )}

                {isDefaultView() && <MapSaveView />}
                {mapBounds && <MapBound mapBounds={mapBounds} />}
                {!drawRoute && <MapPan flyToCoordinates={coordinates} />}
                {drawRoute && <MapDrawRoute departure={drawRoute[0]} arrival={drawRoute[1]} reverseDirection={reverseDirection}/>}
            </MapContainer>
            {showAirportIdCard && <PopupContainer airportId={showAirportIdCard} />}
        </MapContext.Provider>
    );
}

export default Map;

const root = ReactDOM.createRoot(document.getElementById('map'));
root.render(
    <Map />
);
