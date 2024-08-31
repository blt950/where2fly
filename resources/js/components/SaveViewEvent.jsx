import React from 'react';
import { useMapEvents } from 'react-leaflet';

const SaveViewEvent = () => {
    const map = useMapEvents({
        moveend() {
            localStorage.setItem('mapPosition', JSON.stringify(map.getCenter()));
        },
    });

    return null;
};

export default SaveViewEvent;