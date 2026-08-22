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
const LIGHT_PALETTE = { fallback: '#8a6d0b', candidate: '#5a5a5a' };

export const MAP_THEMES = {
    default: {
        label: 'Default',
        style: cartoStyle('dark-matter-nolabels'),
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
        style: cartoStyle('positron-nolabels'),
        overrides: [],
        sky: sky('#cfd9e6', '#d4dadc', '#fafaf8'),
        palette: LIGHT_PALETTE,
    },
};

export const themeOf = (key) => MAP_THEMES[key] ?? MAP_THEMES.default;
export const GLYPHS_URL = '/fonts/{fontstack}/{range}.pbf';
export const LABEL_FONT = ['Work Sans Regular'];

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
    dragPan: { maxSpeed: 1000 },
});

export const insertBefore = (map, ids) => ids.find((id) => map.getLayer(id));
export const applyThemeOverrides = (map, theme) => {
    theme.overrides.forEach(([layer, property, value]) => {
        if (map.getLayer(layer)) { map.setPaintProperty(layer, property, value); }
    });
};