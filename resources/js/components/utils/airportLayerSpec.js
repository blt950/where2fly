import { LABEL_FONT } from '../map/mapConfig';

export const ROOT_FONT_SIZE = parseFloat(getComputedStyle(document.documentElement).fontSize) || 16;

export const NOT_A_CLUSTER = ['!', ['has', 'point_count']];

// The focused airport takes the brand colour; everything else keeps the colour it came with.
export const focusColor = (focusAirport, palette) => ['case',
    ['==', ['get', 'icao'], focusAirport ?? ''], palette.fallback, ['to-color', ['get', 'color']]];

const AIRPORT_TYPES = ['large_airport', 'medium_airport', 'small_airport'];

// One label layer per size, so the smaller ones can be held back until the map is zoomed in.
export const labelIds = (prefix) =>
    AIRPORT_TYPES.map((airportType) => [`${prefix}-label-${airportType.replace('_airport', '')}`, airportType]);

// Search results are dense enough that labelling every airport at world zoom is unreadable;
// the curated lists are not, so they label from zoom 0.
const filtersLabelsByZoom = () => route().current('search') || route().current() === undefined;
const ZOOM_GATE = { large_airport: 0, medium_airport: 6, small_airport: 8 };
export const labelMinzoom = (airportType) => (filtersLabelsByZoom() ? ZOOM_GATE[airportType] : 0);

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

// MapLibre's stock cluster layers, with our palette, typeface and count size swapped in. The
// circle step breaks and radii are the upstream example's — deliberately not tuned.
export const clusterSpecs = ({ idPrefix, source, color, textColor }) => ([
    {
        id: `${idPrefix}-clusters`,
        type: 'circle',
        source,
        filter: ['has', 'point_count'],
        paint: {
            'circle-color': color,
            'circle-radius': ['step', ['get', 'point_count'], 20, 100, 30, 750, 40],
        },
    },
    {
        id: `${idPrefix}-cluster-count`,
        type: 'symbol',
        source,
        filter: ['has', 'point_count'],
        layout: {
            'text-field': '{point_count_abbreviated}',
            'text-font': LABEL_FONT,
            'text-size': ROOT_FONT_SIZE,
        },
        paint: { 'text-color': textColor },
    },
]);
