import { LABEL_FONT } from '../map/mapConfig';

export const ROOT_FONT_SIZE = parseFloat(getComputedStyle(document.documentElement).fontSize) || 16;

// Zoom/type label filtering only ever applied on /search and the home page — not on /top,
// /scenery or /search/routes. Preserved from the CSS-class filtering this replaces.
export const filtersLabelsByZoom = () => route().current('search') || route().current() === undefined;

// Leaflet's zoom was integer, so its `zoom > 5` meant 6 and up. MapLibre's is fractional, hence
// minzoom rather than a filter — which is also free at render time and correctly releases
// collision space.
export const LABEL_MINZOOM = {
    large_airport: () => 0,
    medium_airport: () => (filtersLabelsByZoom() ? 6 : 0),
    small_airport: () => (filtersLabelsByZoom() ? 8 : 0),
};

// One ICAO label layer. Shared so the search/top airports and the user's own list render
// identically rather than drifting apart.
export const labelSpec = ({ id, source, filter, minzoom = 0, haloColor, overlap = false }) => ({
    id,
    type: 'symbol',
    source,
    minzoom,
    filter,
    layout: {
        'text-field': ['get', 'icao'],
        'text-font': LABEL_FONT,
        'text-size': ROOT_FONT_SIZE,
        // Leaflet placed the tooltip to the marker's left, so the text ends where the dot begins.
        'text-anchor': 'right',
        'text-offset': [-0.6, 0],
        ...(overlap ? { 'text-allow-overlap': true, 'text-ignore-placement': true } : {}),
    },
    paint: {
        'text-color': ['to-color', ['get', 'color']],
        // GL text has no CSS shadow to fall back on. A halo is what keeps ICAOs legible where
        // the label crosses bright radar, coastline or hillshade.
        'text-halo-color': haloColor,
        'text-halo-width': 2,
        'text-halo-blur': 0.5,
    },
});
