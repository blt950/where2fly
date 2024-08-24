/*
    ***
    Map drawing functions for Where2Fly
    ***
*/

import L, { icon } from 'leaflet';
import '@elfalem/leaflet-curve';
import 'leaflet.markercluster';

window.mapInit = mapInit;
window.mapSaveView = mapSaveView;
window.mapDrawClickableAirports = mapDrawClickableAirports;
window.mapEventCardOpenPan = mapEventCardOpenPan;
window.mapEventZoomTooltips = mapEventZoomTooltips;
window.mapCreateCluster = mapCreateCluster;
window.mapDrawMarker = mapDrawMarker;
window.mapDrawRoute = mapDrawRoute;
window.map = map;

/*
* Function to initialize the map
*/
function mapInit(airportCoordinates = null, focusAirport = null, focusContinent = null){

    // Default lat and lon (Berlin, Europe)
    var lat = 52.51843039016386;
    var lon = 13.395199187248908;

    // Get the last map position from local storage
    var mapPosition = localStorage.getItem('mapPosition');
    if(mapPosition){
        lat = JSON.parse(mapPosition).lat;
        lon = JSON.parse(mapPosition).lng;
    }

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
    
    // Set bounds based on search results so that the map is not too zoomed out
    if(focusAirport !== null && airportCoordinates !== undefined && airportCoordinates !== null){
        var bounds = [];
        Object.values(airportCoordinates).forEach(airport => {
            bounds.push([airport['lat'], airport['lon']]);
        });

        map.fitBounds(bounds, {padding: [50, 50], animate: false, duration: 0});
    }

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
* Save the current map view position to local storage
*/
function mapSaveView(){
    map.on('moveend', function() {
        localStorage.setItem('mapPosition', JSON.stringify(map.getCenter()));
    });
}

/*
* Function to draw clickable airports
*/
function mapDrawClickableAirports(airportMapData, cluster = null){
    Object.values(airportMapData).forEach(airport => {

        // Prepare the on click function
        const onClickFunc = () => {
            var card = document.querySelector('[data-card-id="' + airport.icao + '"]')
            if(card){
                cardOpen(card, 'airport')
            }
        }

        // Draw the marker
        mapDrawMarker(airport.icao, airportMapData[airport.icao].lat, airportMapData[airport.icao].lon, airportMapData[airport.icao].color, onClickFunc, cluster);
    })
}

/*
* Function to pan to an airport when card is opened
*/
function mapEventCardOpenPan(airportMapData){
    document.addEventListener('cardOpened', function(event) {
        var airport = event.detail.cardId;

        // Focus on the airport
        map.panTo([airportMapData[airport].lat, airportMapData[airport].lon],
            {animate: true, duration: 0.5, easeLinearity: 0.25});
    });
}

/*
* Function to hide/show tooltips based on airport size and zoom level
*/
function mapEventZoomTooltips(){

    var zoomEvent = () => {
        if(map.getZoom() >= 8){
            map.eachLayer(function(layer) {
                if(layer.airportType == 'small_airport') layer.openTooltip()
            })
        } else if(map.getZoom() < 8 && map.getZoom() > 5){
            map.eachLayer(function(layer) {
                if(layer.airportType == 'small_airport') layer.closeTooltip()
                if(layer.airportType == 'medium_airport') layer.openTooltip()
            })
        } else if(map.getZoom() <= 5){
            map.eachLayer(function(layer) {
                if(layer.airportType == 'small_airport') layer.closeTooltip()
                if(layer.airportType == 'medium_airport') layer.closeTooltip()
            })
        }
    }

    zoomEvent()

    map.on('zoomend', function() {
        zoomEvent()
    });
}

/*
* Function to create a cluster
*/
function mapCreateCluster(style = null){
    return L.markerClusterGroup({
        showCoverageOnHover: false,
        maxClusterRadius: 40,
        iconCreateFunction: function(cluster) {
            return L.divIcon({ iconSize: [40, 40], html: '<div class="leaflet-marker-icon marker-cluster '+style+'">' + cluster.getChildCount() + '</div>' });
        }
    });
}

/*
* Function to draw a marker
*/

function mapDrawMarker(text, lat, lon, iconColor = null, clickFunction = ()=>{}, cluster = false, airportType = null){
    var color = (iconColor !== null) ? iconColor : "#ddb81c";

    var iconSizePx = 10;
    if(airportType == 'medium_airport'){
        iconSizePx = 7;
    } else if(airportType == 'small_airport'){
        iconSizePx = 5;
    }

    var icon = L.divIcon({
        iconSize: [iconSizePx, iconSizePx],
        html: `<span style="display: block; width: ${iconSizePx}px; height: ${iconSizePx}px; border-radius: 50%; background: ${color}"></span>`
    });

    var marker = new L.marker([lat, lon], { icon:icon }).on('click', clickFunction);
    marker.bindTooltip(`<span data-airport-type="${airportType}" style="color: ${color};">${text}</span>`, {permanent: true, direction: 'left', className: "airport", interactive: true})
    marker.airportType = airportType;

    if(cluster !== false){
        cluster.addLayer(marker);
    } else {
        marker.addTo(map);
    }

    return marker
}

/*
* Function to draw a route
*/
var routePath = null;
var secondaryMarker = null;
function mapDrawRoute(primaryAirport, destinationAirport, reverseDirection = false){
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
                primaryMarker = mapDrawMarker(primaryAirport, airportCoordinates[primaryAirport]['lat'], airportCoordinates[primaryAirport]['lon']-=360)
            }
        } else {
            latlng1[1] += 360;

            // Remove the primary marker and redraw it with the adjusted lon
            if(primaryMarker) {
                primaryMarker.remove()
                primaryMarker = mapDrawMarker(primaryAirport, airportCoordinates[primaryAirport]['lat'], airportCoordinates[primaryAirport]['lon']+=360)
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
    secondaryMarker = mapDrawMarker(destinationAirport, airportCoordinates[destinationAirport]['lat'], airportCoordinates[destinationAirport]['lon'])

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
