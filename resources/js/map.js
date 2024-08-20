/*
    ***
    Map drawing functions for Where2Fly
    ***
*/

import L, { icon } from 'leaflet';
import '@elfalem/leaflet-curve';
import 'leaflet.markercluster';

window.initMap = initMap;
window.drawUserLists = drawUserLists;
window.createCluster = createCluster;
window.drawMarker = drawMarker;
window.drawRoute = drawRoute;
window.map = map;

/*
* Function to initialize the map
*/
function initMap(airportCoordinates = null, focusAirport = null, focusContinent = null){

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
        [-85, -360], // Southwest corner of the bounds
        [85, 360]    // Northeast corner of the bounds
    ]);

    // Set title provider and zoom restrictions
    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_nolabels/{z}/{x}/{y}{r}.png', {
        minZoom: 3,
        maxZoom: 17,
    }).addTo(map);
}

/*
* Function to draw a user's lists
*/
function drawUserLists(lists){
    var cluster = createCluster();
    var drawnAirports = [];

    JSON.parse(lists).forEach(list => {
        list.airports.forEach(airport => {
            if(drawnAirports.includes(airport.icao)) return;
            drawnAirports.push(airport.icao);
            drawDivMarker(airport.icao, airport.lat, airport.lon, list.color, cluster);
        });
    });

    map.addLayer(cluster);
}

/*
* Function to create a cluster
*/
function createCluster(){
    return L.markerClusterGroup({
        showCoverageOnHover: false,
        maxClusterRadius: 40
    });
}

/*
* Function to draw a marker
*/
function drawMarker(text, lat, lon, iconUrl, clickFunction = ()=>{}, cluster = false){

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

    return marker

}

/*
* Function to draw a div marker
*/
function drawDivMarker(text, lat, lon, iconColor, cluster){

    var icon = L.divIcon({
        iconSize: [12, 12],
        html: '<span style="display: block; width: 10px; height: 10px; border-radius: 50%; background: '+iconColor+'"></span>'
    });

    var marker = new L.marker([lat, lon], { icon:icon });
    marker.bindTooltip(`<span style="color: ${iconColor};">${text}</span`, {permanent: true, direction: 'left', className: "listed-airport"});

    cluster.addLayer(marker);

    return marker

}

/*
* Function to draw a route
*/
var routePath = null;
var secondaryMarker = null;
function drawRoute(primaryAirport, destinationAirport, iconUrl, reverseDirection = false){
    var latlng1 = [];
    var latlng2 = [];

    if(reverseDirection === true){
        latlng1 = [airportCoordinates[destinationAirport]['lat'], airportCoordinates[destinationAirport]['lon']]
        latlng2 = [airportCoordinates[primaryAirport]['lat'], airportCoordinates[primaryAirport]['lon']]
    } else {
        latlng1 = [airportCoordinates[primaryAirport]['lat'], airportCoordinates[primaryAirport]['lon']]
        latlng2 = [airportCoordinates[destinationAirport]['lat'], airportCoordinates[destinationAirport]['lon']]
    }

    // Adjust for shortest path across the International Date Line
    if (Math.abs(latlng2[1] - latlng1[1]) > 180) {
        if (latlng1[1] > 0) {
            latlng1[1] -= 360;

            // Remove the primary marker and redraw it with the adjusted lon
            if(primaryMarker) {
                primaryMarker.remove()
                primaryMarker = drawMarker(primaryAirport, airportCoordinates[primaryAirport]['lat'], airportCoordinates[primaryAirport]['lon']-=360, iconUrl)
            }
        } else {
            latlng1[1] += 360;

            // Remove the primary marker and redraw it with the adjusted lon
            if(primaryMarker) {
                primaryMarker.remove()
                primaryMarker = drawMarker(primaryAirport, airportCoordinates[primaryAirport]['lat'], airportCoordinates[primaryAirport]['lon']+=360, iconUrl)
            }
        }
    }

    // Path color and weight
    var pathOptions = {
        color: '#ddb81c',
        weight: 2,
        renderer: L.svg()
    }

    // Calculations of midpoint and animation
    var calcMidLatLng = calcMidpointLatLng(latlng1, latlng2)
    var midpointLatLng = calcMidLatLng[0]
    var r = calcMidLatLng[1]
    var duration = Math.sqrt(Math.log(r)) * 200;

    pathOptions.animate = {
        duration: duration,
        iterations: 1,
        easing: 'ease-in-out',
        direction: 'alternate',
    }

    // Delete old path and secondary marker if applicable
    if(routePath) { routePath.remove() }
    if(secondaryMarker) { secondaryMarker.remove() }

    // Draw the destination airport marker
    secondaryMarker = drawMarker(destinationAirport, airportCoordinates[destinationAirport]['lat'], airportCoordinates[destinationAirport]['lon'], iconUrl)

    // Draw the path
    routePath = L.curve(
        [
            'M', latlng1,
            'Q', midpointLatLng,
                latlng2
        ], pathOptions)

    // Fly to bounds but with adjusted padding according to screen size
    if(window.innerWidth > 1920){
        map.flyToBounds(routePath.getBounds(), {duration: 0.35, minZoom: 3, maxZoom: 7, paddingTopLeft: [400, 350], paddingBottomRight: [75, 50]});
    } else {
        map.flyToBounds(routePath.getBounds(), {duration: 0.35, minZoom: 3, maxZoom: 7, paddingTopLeft: [50, 350], paddingBottomRight: [50, 50]});
    }
    
    // Start drawing the path once map is done moving
    map.once('moveend', function() {
        routePath.addTo(map);
    });

    // Redraw route path with canvas renderer to fix the line becoming dashes
    setTimeout(() => {

        routePath.remove();

        pathOptions.renderer = L.canvas();
        pathOptions.animate = [];

        routePath = L.curve(
            [
                'M', latlng1,
                'Q', midpointLatLng,
                    latlng2
            ], pathOptions).addTo(map);
    }, 350 + duration);
}

/*
* Function to calculate the midpoint between two latlngs
*/
function calcMidpointLatLng(latlng1, latlng2){
    var offsetX = latlng2[1] - latlng1[1],
        offsetY = latlng2[0] - latlng1[0];

    var r = Math.sqrt(Math.pow(offsetX, 2) + Math.pow(offsetY, 2)),
        theta = Math.atan2(offsetY, offsetX);

    // Determine the thetaOffset based on the position relative to the equator and the east-west direction
    var thetaOffset;

    if (latlng1[0] >= 0) { // Origin north of the equator
        if (offsetX >= 0) { // Destination is eastbound
            thetaOffset = (3.14 / 10); // Curve slightly to the right (default)
        } else { // Destination is westbound
            thetaOffset = -(3.14 / 10); // Curve slightly to the left
        }
    } else { // Origin south of the equator
        if (offsetX >= 0) { // Destination is eastbound
            thetaOffset = -(3.14 / 10); // Curve slightly to the left
        } else { // Destination is westbound
            thetaOffset = (3.14 / 10); // Curve slightly to the right
        }
    }

    var r2 = (r / 2) / (Math.cos(thetaOffset)),
        theta2 = theta + thetaOffset;

    var midpointX = (r2 * Math.cos(theta2)) + latlng1[1],
        midpointY = (r2 * Math.sin(theta2)) + latlng1[0];

    return [[midpointY, midpointX], r];
}
