/*
    ***
    Map drawing functions for Where2Fly
    ***
*/

import L from 'leaflet';
import '@elfalem/leaflet-curve';
import 'leaflet.markercluster';

window.initMap = initMap;
window.createCluster = createCluster;
window.drawMarker = drawMarker;
window.map = map;

/*
* Function to initialize the map
*/
function initMap(airportCoordinates, focusAirport = null, focusContinent = null){

    // Default lat and lon (Berlin, Europe)
    var lat = 52.51843039016386;
    var lon = 13.395199187248908;

    // If focusAirport is set, set focus there instead
    if(focusAirport !== null && airportCoordinates !== undefined && Object.keys(airportCoordinates).length > 0){
        lat = airportCoordinates[focusAirport]['lat'];
        lon = airportCoordinates[focusAirport]['lon'];
    }

    // If focusContinent is set, set focus there instead
    if(focusContinent !== null){
        if(focusContinent == 'AF'){
            lat = 7.1881;
            lon = 21.0936;
        } else if(focusContinent == 'AS'){
            lat = 34.0479;
            lon = 100.6197;
        } else if(focusContinent == 'EU'){
            lat = 54.5260;
            lon = 15.2551;
        } else if(focusContinent == 'NA'){
            lat = 37.0902;
            lon = -95.7129;
        } else if(focusContinent == 'OC'){
            lat = -25.2744;
            lon = 133.7751;
        } else if(focusContinent == 'SA'){
            lat = -8.7832;
            lon = -55.4915;
        }
    }

    // Initiate map with a global map variable
    map = L.map('map', {
        attributionControl: false,
        zoomControl: false,
    }).setView([lat, lon], 4);

    // Set max bounds for map
    map.setMaxBounds([
        [-85, -250], // Southwest corner of the bounds
        [85, 250]    // Northeast corner of the bounds
    ]);

    // Set title provider and zoom restrictions
    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_nolabels/{z}/{x}/{y}{r}.png', {
        minZoom: 3,
        maxZoom: 17,
    }).addTo(map);
}

/*
* Function to create a cluster
*/
function createCluster(){
    return L.markerClusterGroup({
        showCoverageOnHover: false,
    });
}

/*
* Function to draw a marker
*/
function drawMarker(text, lat, lon, iconUrl, clickFunction = null, cluster = false){

    var icon = L.icon({
        iconUrl: iconUrl,
        iconSize: [12, 12],
    });

    var marker = new L.marker([lat, lon], { icon:icon }).on('click', clickFunction);
    marker.bindTooltip(text, {permanent: true, direction: 'left', className: "airport"});

    if(cluster !== false){
        cluster.addLayer(marker);
    } else {
        marker.addTo(map);
    }

}