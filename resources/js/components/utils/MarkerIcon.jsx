import { DivIcon } from 'leaflet';

const createMarkerIcon = (color, airportType = 'large_airport') => {
    let sizePx = 10;
    if(color === null || color === undefined){ color = '#ddb81c'; }

    if (airportType === 'medium_airport') {
        sizePx = 7;
    } else if (airportType === 'small_airport') {
        sizePx = 5;
    }

    return new DivIcon({
        iconSize: [sizePx, sizePx],
        html: `<span class="dot" style="display: block; width: ${sizePx}px; height: ${sizePx}px; border-radius: 50%; background: ${color}"></span>`
    });
};

export { createMarkerIcon };