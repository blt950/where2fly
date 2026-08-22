const D2R = Math.PI / 180;
const R2D = 180 / Math.PI;

// Angular separation of two [lon, lat] points, in degrees — the great-circle arc length.
export const arcDegrees = ([lon1, lat1], [lon2, lat2]) => {
    const p1 = lat1 * D2R, p2 = lat2 * D2R;

    return 2 * Math.asin(Math.sqrt(
        Math.sin((p2 - p1) / 2) ** 2
        + Math.cos(p1) * Math.cos(p2) * Math.sin((lon2 - lon1) * D2R / 2) ** 2)) * R2D;
};

// Spherical interpolation between two [lon, lat] points — the line an aircraft actually flies,
// not the cosmetic Bézier this replaces. Emits CONTINUOUS longitudes: values may run past ±180
// so a date-line route stays one unbroken line in both globe and mercator.
export const greatCircle = ([lon1, lat1], [lon2, lat2], segments) => {
    const p1 = lat1 * D2R, l1 = lon1 * D2R, p2 = lat2 * D2R, l2 = lon2 * D2R;
    const d = arcDegrees([lon1, lat1], [lon2, lat2]) * D2R;

    if (!Number.isFinite(d) || d < 1e-9) {
        return [[lon1, lat1], [lon2, lat2]];
    }

    // Near-antipodal: sin(d) collapses and the great circle is undefined. It never happens for
    // a real airport pair, but a NaN coordinate silently blanks the whole layer.
    if (Math.abs(Math.PI - d) < 1e-6) {
        return [[lon1, lat1], [lon2, lat2]];
    }

    // Roughly a point per degree. At 111km a chord's sagitta error is ~0.24km — sub-pixel here.
    const n = segments ?? Math.min(256, Math.max(32, Math.ceil(d * R2D)));
    const points = [];
    let previousLon = lon1;

    for (let i = 0; i <= n; i++) {
        const f = i / n;
        const a = Math.sin((1 - f) * d) / Math.sin(d);
        const b = Math.sin(f * d) / Math.sin(d);

        const x = a * Math.cos(p1) * Math.cos(l1) + b * Math.cos(p2) * Math.cos(l2);
        const y = a * Math.cos(p1) * Math.sin(l1) + b * Math.cos(p2) * Math.sin(l2);
        const z = a * Math.sin(p1) + b * Math.sin(p2);

        let lon = Math.atan2(y, x) * R2D;

        // Unwrap: keep every step within ±180 of the one before it.
        while (lon - previousLon > 180) { lon -= 360; }
        while (lon - previousLon < -180) { lon += 360; }

        points.push([lon, Math.atan2(z, Math.hypot(x, y)) * R2D]);
        previousLon = lon;
    }

    return points;
};

// Bounding box of [lon, lat] points that takes the short way round the planet. Pushing raw
// longitudes into a box, as MapBound and Map.jsx used to, turns a Tokyo-San Francisco pair into
// a 262°-wide box across the Atlantic and flies the camera the wrong way around the world.
export const boundsFromCoordinates = (coordinates) => {
    const lats = coordinates.map(([, lat]) => lat);
    const lons = coordinates.map(([lon]) => lon).sort((a, b) => a - b);

    // The bounding arc is the complement of the widest empty gap between longitudes.
    let gapStart = lons.length - 1;
    let widest = lons[0] + 360 - lons[lons.length - 1];

    for (let i = 0; i < lons.length - 1; i++) {
        if (lons[i + 1] - lons[i] > widest) {
            widest = lons[i + 1] - lons[i];
            gapStart = i;
        }
    }

    const west = lons[(gapStart + 1) % lons.length];
    const east = lons[gapStart];

    return [[west, Math.min(...lats)], [east < west ? east + 360 : east, Math.max(...lats)]];
};
