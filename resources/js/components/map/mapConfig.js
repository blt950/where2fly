const cartoStyle = (name) => `https://basemaps.cartocdn.com/gl/${name}-gl-style/style.json`;

// CARTO ships no sky block, and dark land against a dark page leaves the globe's limb
// invisible. horizon-color is each palette's own water colour; the atmosphere fades out by z7
// so the search and results views still look like the flat map they replaced.
const sky = (skyColor, horizon, fog) => ({
    'sky-color': skyColor,
    'horizon-color': horizon,
    'fog-color': fog,
    'sky-horizon-blend': 0.5,
    'horizon-fog-blend': 0.6,
    'atmosphere-blend': ['interpolate', ['linear'], ['zoom'], 0, 0.6, 5, 0.4, 7, 0],
});

// Land and sea live in exactly two layers of CARTO's styles — `background` and `water` — so a
// palette variant is a couple of paint overrides rather than a whole second stylesheet.
// `waterway` (rivers) moves with the sea so inland water does not stay the old hue.
// Brand gold sits at about 1.9:1 on positron's #fafaf8 land, so the light theme needs its own
// pair. User-chosen scenery list colours are left exactly as the user picked them.
const DARK_PALETTE = { fallback: '#ddb81c', candidate: '#808080' };
const LIGHT_PALETTE = { fallback: '#8a6d0b', candidate: '#5a5a5a' };

export const MAP_THEMES = {
    default: {
        label: 'Default',
        style: cartoStyle('dark-matter-nolabels'),
        // Spelled out even though they match the stylesheet: Default and Darker share a
        // stylesheet, so switching between them has to restate the palette rather than rely on
        // a reload to restore it.
        overrides: [
            ['background', 'background-color', '#0e0e0e'],
            ['water', 'fill-color', '#2C353C'],
            ['waterway', 'line-color', 'rgba(63, 90, 109, 1)'],
        ],
        sky: sky('#05070c', '#2C353C', '#0e0e0e'),
        palette: DARK_PALETTE,
    },
    darker: {
        label: 'Darker',
        style: cartoStyle('dark-matter-nolabels'),
        overrides: [
            ['background', 'background-color', '#090909'],
            ['water', 'fill-color', '#262626'],
            ['waterway', 'line-color', '#3a3a3a'],
        ],
        sky: sky('#040404', '#262626', '#090909'),
        palette: DARK_PALETTE,
    },
    light: {
        label: 'Light',
        // Positron is CARTO's own light counterpart to dark-matter: same family, same layer
        // ids, free and keyless. Land #fafaf8, water #d4dadc.
        style: cartoStyle('positron-nolabels'),
        overrides: [],
        sky: sky('#cfd9e6', '#d4dadc', '#fafaf8'),
        // Light basemaps need dark text; the palette darkens rather than the type gaining an
        // outline.
        palette: LIGHT_PALETTE,
    },
};

export const themeOf = (key) => MAP_THEMES[key] ?? MAP_THEMES.default;

// Self-hosted so ICAO labels keep the site's own typeface: CARTO's glyph server has Open
// Sans, Roboto and Noto, but 404s on Work Sans. Rebuild with scripts/build-glyphs.mjs.
export const GLYPHS_URL = '/fonts/{fontstack}/{range}.pbf';
export const LABEL_FONT = ['Work Sans Regular'];

// maxBounds is deliberately absent: the globe transform does not support constraining the
// centre, and Leaflet's ±360 world-copy trick has no meaning on a sphere.
export const mapOptions = (container, center, style) => ({
    container,
    style,
    center,
    zoom: 4,
    minZoom: 0,
    maxZoom: 17,
    attributionControl: false,
    dragRotate: false,
    pitchWithRotate: false,
    touchPitch: false,
    // No pan inertia. maxSpeed: 0 is what disables it; linearity must stay above 0 because
    // the inertia duration is speed/(deceleration*linearity), and 0/0 wedges panning.
    dragPan: { linearity: 0.3, maxSpeed: 0, deceleration: 2500 },
});

// Resolves the first of `ids` that is currently on the map, for use as addLayer's beforeId.
// The overlay components mount independently and can each be toggled off, so no single layer
// id is guaranteed to be there when another one needs to insert beneath it.
export const insertBefore = (map, ids) => ids.find((id) => map.getLayer(id));

// Repaints the basemap's land and sea for the chosen palette. Applied on every style.load,
// since a style swap resets everything the previous one had been told.
export const applyThemeOverrides = (map, theme) => {
    theme.overrides.forEach(([layer, property, value]) => {
        if (map.getLayer(layer)) { map.setPaintProperty(layer, property, value); }
    });
};
