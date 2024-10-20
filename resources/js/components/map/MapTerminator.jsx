import { useContext, useEffect, useRef } from 'react';
import { useMap } from 'react-leaflet';
import L from 'leaflet';
import terminator from '@joergdietrich/leaflet.terminator';

const MapTerminator = () => {

    const map = useMap();
    useEffect(() => {
        terminator({fillOpacity: 0.3}).addTo(map);
    }, []);

};
        
export default MapTerminator;