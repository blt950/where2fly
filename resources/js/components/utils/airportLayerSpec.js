import { LABEL_FONT } from '../map/mapConfig';

export const ROOT_FONT_SIZE = parseFloat(getComputedStyle(document.documentElement).fontSize) || 16;
export const filtersLabelsByZoom = () => route().current('search') || route().current() === undefined;

export const LABEL_MINZOOM = {
    large_airport: () => 0,
    medium_airport: () => (filtersLabelsByZoom() ? 6 : 0),
    small_airport: () => (filtersLabelsByZoom() ? 8 : 0),
};

// One ICAO label layer. Shared so the search/top airports and the user's own list render
// identically rather than drifting apart.
export const labelSpec = ({ id, source, filter, minzoom = 0, overlap = false }) => ({
    id,
    type: 'symbol',
    source,
    minzoom,
    filter,
    layout: {
        'text-field': ['get', 'icao'],
        'text-font': LABEL_FONT,
        'text-size': ROOT_FONT_SIZE,
        'text-anchor': 'right',
        'text-offset': [-0.6, 0],
        ...(overlap ? { 'text-allow-overlap': true, 'text-ignore-placement': true } : {}),
    },
    paint: { 'text-color': ['to-color', ['get', 'color']] },
});
