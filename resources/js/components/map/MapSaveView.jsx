import { useMapEvents } from 'react-leaflet';

const MapSaveView = () => {
    const map = useMapEvents({
        moveend() {
            localStorage.setItem('mapPosition', JSON.stringify(map.getCenter()));
        },
    });

    return null;
};

export default MapSaveView;