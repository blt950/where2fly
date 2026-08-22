import { LABEL_FONT } from '../map/mapConfig';

export const ROOT_FONT_SIZE = parseFloat(getComputedStyle(document.documentElement).fontSize) || 16;
export const filtersLabelsByZoom = () => route().current('search') || route().current() === undefined;

export const NOT_A_CLUSTER = ['!', ['has', 'point_count']];

// The focused airport takes the brand colour; everything else keeps the colour it came with.
export const focusColor = (focusAirport, palette) => ['case',
    ['==', ['get', 'icao'], focusAirport ?? ''], palette.fallback, ['to-color', ['get', 'color']]];

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

// Bubble grows with the log of the count, so a 2-airport cluster and a 2,000-airport one stay
// within a readable range of each other.
const clusterScale = (minRem, maxRem) => ['interpolate', ['linear'], ['ln', ['get', 'point_count']],
    Math.log(2), minRem * ROOT_FONT_SIZE, Math.log(100), maxRem * ROOT_FONT_SIZE];

// Cluster bubble and its count. Shared so the user's lists cluster exactly like search results.
export const clusterSpecs = ({ idPrefix, source, color, textColor }) => ([
    {
        id: `${idPrefix}-clusters`,
        type: 'circle',
        source,
        filter: ['has', 'point_count'],
        paint: { 'circle-radius': clusterScale(2 / 2, 3.75 / 2), 'circle-color': color },
    },
    {
        id: `${idPrefix}-cluster-count`,
        type: 'symbol',
        source,
        filter: ['has', 'point_count'],
        layout: {
            'text-field': ['get', 'point_count_abbreviated'],
            'text-font': LABEL_FONT,
            'text-size': clusterScale(0.75, 3.75 * 0.35),
            'text-allow-overlap': true,
        },
        paint: { 'text-color': textColor },
    },
]);
