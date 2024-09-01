import React, { useState, useEffect, useRef } from 'react';
import L from 'leaflet';
import { useMap } from 'react-leaflet';
import MapMarker from './MapMarker';

const DrawRoute = ({ airports, departure, arrival, reverseDirection = true }) => {
    const routePath = useRef(null);
    const map = useMap();
    
    useEffect(() => {
        let latlng1 = [];
        let latlng2 = [];
        
        if (reverseDirection) {
            latlng1 = [airports[departure].lat, airports[departure].lon];
            latlng2 = [airports[arrival].lat, airports[arrival].lon];
        } else {
            latlng1 = [airports[arrival].lat, airports[arrival].lon];
            latlng2 = [airports[departure].lat, airports[departure].lon];
        }
        
        // Adjust for shortest path across the International Date Line
        if (Math.abs(latlng2[1] - latlng1[1]) > 180) {
            if (latlng1[1] > 0) {
                latlng1[1] -= 360;
                airports[primaryAirport].lon = airports[primaryAirport].lon -= 360
            } else {
                latlng1[1] += 360;
                airports[primaryAirport].lon = airports[primaryAirport].lon += 360
            }
        }
        
        // Path color and weight
        const pathOptions = {
            color: '#ddb81c',
            weight: 2,
            renderer: L.svg(),
        };
        
        // Calculations of midpoint and animation
        const calcMidLatLng = calcMidpointLatLng(latlng1, latlng2);
        const midpointLatLng = calcMidLatLng[0];
        const r = calcMidLatLng[1];
        const duration = Math.sqrt(Math.log(r)) * 200;
        
        pathOptions.animate = {
            duration: duration,
            iterations: 1,
            easing: 'ease-in-out',
            direction: 'alternate',
        };
        
        // Delete old paths
        if (routePath.current !== null){
            routePath.current.remove();
            routePath.current = null;
        }
        
        // Draw the path
        routePath.current = L.curve(['M', latlng1, 'Q', midpointLatLng, latlng2], pathOptions);
        
        // Fly to bounds but with adjusted padding according to screen size
        if (window.innerWidth > 1920) {
            map.flyToBounds(routePath.current.getBounds(), { duration: 0.35, minZoom: 3, maxZoom: 7, paddingTopLeft: [400, 350], paddingBottomRight: [75, 50] });
        } else {
            map.flyToBounds(routePath.current.getBounds(), { duration: 0.35, minZoom: 3, maxZoom: 7, paddingTopLeft: [50, 350], paddingBottomRight: [50, 50] });
        }
        
        // Start drawing the path once map is done moving
        map.once('moveend', function () {
            routePath.current.addTo(map);
        });
        
        // Redraw route path with canvas renderer to fix the line becoming dashes
        setTimeout(() => {
            routePath.current.remove();
            
            pathOptions.renderer = L.canvas();
            pathOptions.animate = [];
            
            routePath.current = L.curve(['M', latlng1, 'Q', midpointLatLng, latlng2], pathOptions).addTo(map);
                
        }, 350 + duration);
    }, [airports, departure, arrival]);
            
    const calcMidpointLatLng = (latlng1, latlng2) => {
        const offsetX = latlng2[1] - latlng1[1];
        const offsetY = latlng2[0] - latlng1[0];
        
        const r = Math.sqrt(Math.pow(offsetX, 2) + Math.pow(offsetY, 2));
        const theta = Math.atan2(offsetY, offsetX);
        
        // Determine the thetaOffset based on the position relative to the equator and the east-west direction
        let thetaOffset;
        
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
        
        const r2 = (r / 2) / (Math.cos(thetaOffset));
        const theta2 = theta + thetaOffset;
        
        const midpointX = (r2 * Math.cos(theta2)) + latlng1[1];
        const midpointY = (r2 * Math.sin(theta2)) + latlng1[0];
        
        return [[midpointY, midpointX], r];
    };
};
        
export default DrawRoute;