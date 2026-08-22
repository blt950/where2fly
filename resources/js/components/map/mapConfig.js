// CARTO's vector twin of the dark_nolabels raster style we used with Leaflet: free, no API
// key, same palette (land #0e0e0e, water #2C353C).
export const MAP_STYLE = 'https://basemaps.cartocdn.com/gl/dark-matter-nolabels-gl-style/style.json';

// CARTO ships no sky block, and #0e0e0e land against a black page leaves the globe's limb
// invisible. horizon-color is CARTO's own water colour; the atmosphere fades out by z7 so the
// search and results views still look like the flat map they replaced.
export const SKY = {
    'sky-color': '#05070c',
    'horizon-color': '#2C353C',
    'fog-color': '#0e0e0e',
    'sky-horizon-blend': 0.5,
    'horizon-fog-blend': 0.6,
    'atmosphere-blend': ['interpolate', ['linear'], ['zoom'], 0, 0.6, 5, 0.4, 7, 0],
};

// Self-hosted so ICAO labels keep the site's own typeface: CARTO's glyph server has Open
// Sans, Roboto and Noto, but 404s on Work Sans. Rebuild with scripts/build-glyphs.mjs.
export const GLYPHS_URL = '/fonts/{fontstack}/{range}.pbf';
export const LABEL_FONT = ['Work Sans Regular'];

// maxBounds is deliberately absent: the globe transform does not support constraining the
// centre, and Leaflet's ±360 world-copy trick has no meaning on a sphere.
export const mapOptions = (container, center) => ({
    container,
    style: MAP_STYLE,
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
