import { useContext } from 'react';
import MapMarker from './MapMarker';
import { MapContext } from '../context/MapContext';

const MapMarkerGroup = () => {

    const { airports, focusAirport, primaryAirport, setFocusAirport } = useContext(MapContext);

    return (
        Object.keys(airports).map(key => {
            const airport = airports[key];
            return (
                <MapMarker
                    key={key}
                    airport={airport}
                    isFocused={focusAirport === airport.icao}
                    isPrimary={primaryAirport === airport.icao}
                    setFocusAirport={setFocusAirport}
                />
            );
        })
    );
};

export default MapMarkerGroup;
