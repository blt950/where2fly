import { LABEL_FONT } from '../map/mapConfig';

export const ROOT_FONT_SIZE = parseFloat(getComputedStyle(document.documentElement).fontSize) || 16;

export const NOT_A_CLUSTER = ['!', ['has', 'point_count']];

// The focused airport takes the brand color; everything else keeps the color it came with.
export const focusColor = (focusAirport, palette) => ['case',
    ['==', ['get', 'icao'], focusAirport ?? ''], palette.fallback, ['to-color', ['get', 'color']]];

// MapAirportSource is mounted once per data set and builds every id from these prefixes; the
// overlays stack against the results source's hit layer, so it is named here too.
export const AIRPORT_SOURCES = { results: 'airports', userLists: 'user-list' };
export const hitId = (prefix) => `${prefix}-hit`;
export const clusterIds = (prefix) => [`${prefix}-clusters`, `${prefix}-cluster-count`];

const AIRPORT_TYPES = ['large_airport', 'medium_airport', 'small_airport'];
const CATCH_ALL = AIRPORT_TYPES[AIRPORT_TYPES.length - 1];

// One label layer per size, so the smaller ones can be held back until the map is zoomed in.
export const labelIds = (prefix) =>
    AIRPORT_TYPES.map((airportType) => [`${prefix}-label-${airportType.replace('_airport', '')}`, airportType]);

// The smallest tier is the catch-all: heliports, seaplane bases and anything else label with it
// rather than going unlabelled, which is what Leaflet's per-marker tooltips did.
export const labelTypeFilter = (airportType) => (airportType === CATCH_ALL
    ? ['!', ['in', ['get', 'type'], ['literal', AIRPORT_TYPES.slice(0, -1)]]]
    : ['==', ['get', 'type'], airportType]);

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

export const clusterSpecs = ({ idPrefix, source, color, textColor }) => ([
    {
        id: clusterIds(idPrefix)[0],
        type: 'circle',
        source,
        filter: ['has', 'point_count'],
        paint: {
            'circle-color': color,
            'circle-radius': ['step', ['get', 'point_count'], 20, 100, 30, 750, 40],
        },
    },
    {
        id: clusterIds(idPrefix)[1],
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
