const cartoStyle = (name) => `https://basemaps.cartocdn.com/gl/${name}-gl-style/style.json`;

const sky = (skyColor, horizon, fog) => ({
    'sky-color': skyColor,
    'horizon-color': horizon,
    'fog-color': fog,
    'sky-horizon-blend': 0.5,
    'horizon-fog-blend': 0.6,
    'atmosphere-blend': ['interpolate', ['linear'], ['zoom'], 0, 0.6, 5, 0.4, 7, 0],
});

const DARK_PALETTE = { fallback: '#ddb81c', candidate: '#808080' };
const LIGHT_PALETTE = { fallback: '#695310', candidate: '#5a5a5a' };

// Land is near-black on the dark themes, so shadow and accent have almost nothing to darken —
// the relief has to be carried by the lit slopes, which is why the highlight runs this bright.
const DARK_HILLSHADE = {
    'hillshade-exaggeration': ['interpolate', ['linear'], ['zoom'], 0, 0.85, 5, 0.75, 9, 0.55],
    'hillshade-shadow-color': '#000000',
    'hillshade-highlight-color': '#302f2f',
    'hillshade-accent-color': '#2b2b2b',
};

// Inverted on the near-white positron land: the highlight is what disappears, so the shading
// does the work.
const LIGHT_HILLSHADE = {
    'hillshade-exaggeration': ['interpolate', ['linear'], ['zoom'], 0, 0.6, 5, 0.5, 9, 0.35],
    'hillshade-shadow-color': '#4a4a4a',
    'hillshade-highlight-color': '#ffffff',
    'hillshade-accent-color': '#7d7d7d',
};

export const MAP_THEMES = {
    default: {
        label: 'Dark',
        style: cartoStyle('dark-matter-nolabels'),
        overrides: [
            ['background', 'background-color', '#090909'],
            ['water', 'fill-color', '#262626'],
            ['waterway', 'line-color', '#3a3a3a'],
        ],
        sky: sky('#040404', '#262626', '#090909'),
        palette: DARK_PALETTE,
        hillshade: DARK_HILLSHADE,
    },
    light: {
        label: 'Light',
        style: cartoStyle('positron-nolabels'),
        overrides: [],
        sky: sky('#cfd9e6', '#d4dadc', '#fafaf8'),
        palette: LIGHT_PALETTE,
        hillshade: LIGHT_HILLSHADE,
    },
};

export const themeOf = (key) => MAP_THEMES[key] ?? MAP_THEMES.default;
// Search results cluster in the brand gold; every other view uses the muted home-page bubble.
export const CLUSTER_COLOURS = {
    search: { clusterColor: '#ddb81c', clusterTextColor: '#000000' },
    muted: { clusterColor: '#2f3549', clusterTextColor: '#ffffff' },
};

export const GLYPHS_URL = '/fonts/{fontstack}/{range}.pbf';
export const LABEL_FONT = ['Work Sans Regular'];

export const mapOptions = (container, view, style) => ({
    container,
    style,
    center: view.center,
    zoom: view.zoom,
    minZoom: 0,
    maxZoom: 17,
    attributionControl: false,
    dragRotate: false,
    pitchWithRotate: false,
    touchPitch: false,
    dragPan: { maxSpeed: 1000 },
});

// Layer ids that cross module boundaries: overlays stack against them, so a rename has to be
// visible from one place. The first two come from the CARTO basemap style.
export const BASEMAP_ANCHORS = { water: 'water', countryBorders: 'boundary_country_inner' };
export const TERMINATOR_LAYER = 'terminator';

// The first of these layers that exists — where an overlay slots into the stack.
export const beneath = (map, ids) => ids.find((id) => map.getLayer(id));
// A style swap resets sky and land colors, so this runs on every load, not once.
export const applyTheme = (map, theme) => {
    theme.overrides.forEach(([layer, property, value]) => {
        if (map.getLayer(layer)) { map.setPaintProperty(layer, property, value); }
    });

    map.setSky(theme.sky);
};