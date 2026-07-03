import { useEffect } from 'react';
import { useMapEvents } from 'react-leaflet';

// Toggles tooltip-visibility classes on the map container based on zoom level, imperatively.
const applyZoomClasses = (map) => {
    const el = map.getContainer();
    const zoom = map.getZoom();

    // Zoom/type tooltip filtering only applies on the search / default views.
    const filter = route().current('search') || route().current() === undefined;

    el.classList.toggle('tt-filter', !!filter);
    el.classList.toggle('tt-show-medium', zoom > 5);
    el.classList.toggle('tt-show-small', zoom >= 8);
};

const MapTooltipZoom = () => {
    const map = useMapEvents({
        zoomend() { applyZoomClasses(map); },
    });

    useEffect(() => { applyZoomClasses(map); }, []);

    return null;
};

export default MapTooltipZoom;
