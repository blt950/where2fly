import React from 'react';
import { Marker, Tooltip } from 'react-leaflet';
import MapMarker from './MapMarker';

const MapMarkerGroup = ({ airports }) => {
    return (
        Object.keys(airports).map(key => {
            const airport = airports[key];
            return <MapMarker key={key} airport={airport} />;
        })
    );
};

export default MapMarkerGroup;