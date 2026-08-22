// Leaflet sized its DivIcons 10/7/5 px across; these are the matching circle radii.
const TYPE_RADIUS = { large_airport: 5, medium_airport: 3.5, small_airport: 2.5 };

// SearchController omits `color` for the primary airport and sends the literal 'grey' for
// candidates. MarkerIcon.jsx fell back to the brand gold on a missing colour. Both are our own
// palette rather than user data, so both follow the theme.
const normalizeColor = (color, palette) => (!color ? palette.fallback : color === 'grey' ? palette.candidate : color);

export const airportsToGeoJson = (airports, palette) => ({
    type: 'FeatureCollection',
    features: Object.values(airports ?? {}).map((airport) => ({
        type: 'Feature',
        geometry: { type: 'Point', coordinates: [Number(airport.lon), Number(airport.lat)] },
        properties: {
            id: airport.id,
            icao: airport.icao,
            type: airport.type ?? 'large_airport',
            color: normalizeColor(airport.color, palette),
            r: TYPE_RADIUS[airport.type] ?? TYPE_RADIUS.large_airport,
        },
    })),
});

export const EMPTY_FEATURE_COLLECTION = { type: 'FeatureCollection', features: [] };
