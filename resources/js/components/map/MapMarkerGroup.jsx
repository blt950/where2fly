import { useContext } from 'react';
import MapMarker from './MapMarker';
import { MapContext } from '../context/MapContext';

const MapMarkerGroup = () => {

    const {airports} = useContext(MapContext);

    return (
        Object.keys(airports).map(key => {
            const airport = airports[key];
            return <MapMarker key={key} airport={airport} />;
        })
    );
};

export default MapMarkerGroup;