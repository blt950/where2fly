
import React, { useState, useEffect, useRef } from 'react';
import ReactDOM from 'react-dom/client';
import { MapContainer, TileLayer, Marker, Tooltip } from 'react-leaflet'
import PopupContainer from './PopupContainer';
import MarkerClusterGroup from 'react-leaflet-cluster';
import PanEvent from './PanEvent';
import SaveViewEvent from './SaveViewEvent';
import DrawRoute from './DrawRoute';
import MapMarkerGroup from './MapMarkerGroup';

const isDefaultView = () => {
    if (!route().current('top') 
        && !route().current('top.filtered')
        && !route().current('search')
        && route().current() !== undefined) {
        return true;
    }
    return false
}

const getMapPosition = () => {

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
    const [showAirportCard, setShowAirportCard] = useState(false);
    const [airportId, setAirportId] = useState(null);
    const [mapPosition, setMapPosition] = useState(getMapPosition());
    const [coordinates, setCoordinates] = useState(null);
    const [focusAirport, setFocusAirport] = useState(null);
    const [drawRoute, setDrawRoute] = useState(null);
    const [cluster, setCluster] = useState(true);

    const setAirportsRef = useRef(null);

    useEffect(() => {
        window.setAirportsData = (data) => {
            setAirports(data);
        };

        window.setFocusAirport = (icao) => {
            setFocusAirport(icao);
        };

        window.setDrawRoute = (route) => {
            setDrawRoute(route);
        }

        window.setCluster = (cluster) => {
            setCluster(cluster);
        }

        if (isDefaultView()) {
            fetch(route('api.lists.airports'), { credentials: 'include', headers: { 'Accept': 'application/json' } })
                .then(response => response.json())
                .then(data => setAirports(data.data))
                .catch(error => console.error(error.message));
        }
    
        // Dispatch a custom event when the map is ready
        const event = new Event('mapReady');
        window.dispatchEvent(event);
    
        return () => {
            delete window.setAirportsData;
        };
    }, []);

    useEffect(() => {
        if (focusAirport !== null) {
            setCoordinates([airports[focusAirport].lat, airports[focusAirport].lon]);
            setAirportId(airports[focusAirport].id);
            setShowAirportCard(true);
        }
    }, [focusAirport]);

    const iconCreateFunction = (cluster) => {
        // if url not ends with /top or /search, set style to 'inverted'
        var style = '';
        if (isDefaultView()) {
            style = 'inverted';
        }
        
        return L.divIcon({ 
            iconSize: [40, 40], 
            html: `<div class="leaflet-marker-icon marker-cluster ${style}">${cluster.getChildCount()}</div>` 
        });
    };

    return (
        <>
        <MapContainer 
            className="map" 
            center={mapPosition}
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
                <MarkerClusterGroup showCoverageOnHover={false} maxClusterRadius={40} iconCreateFunction={iconCreateFunction}>
                    <MapMarkerGroup airports={airports}/>
                </MarkerClusterGroup>
            ) : (
                <MapMarkerGroup airports={airports}/>
            )}

            {isDefaultView() && <SaveViewEvent />}
            <PanEvent flyToCoordinates={coordinates} />
            {drawRoute && <DrawRoute airports={airports} departure={drawRoute[0]} arrival={drawRoute[1]}/>}
        </MapContainer>
        {showAirportCard && <PopupContainer airportId={airportId} />}
        </>
    );
}

export default Map;

const root = ReactDOM.createRoot(document.getElementById('map'));
root.render(
    <Map />
);
