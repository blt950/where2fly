// Solar terminator geometry, ported from the MIT-licensed @joergdietrich/leaflet.terminator
// this replaces. Emits GeoJSON [lon, lat] instead of Leaflet's [lat, lng].
const D2R = Math.PI / 180;
const R2D = 180 / Math.PI;

// Points per degree of longitude. Leaflet swept 720° to cover world copies; a globe has none,
// so 360° is the whole planet.
const RESOLUTION = 2;
const LONGITUDE_RANGE = 360;

const julian = (date) => date / 86400000 + 2440587.5;

// Greenwich Mean Sidereal Time, low-precision form — good to a few arcseconds, which is far
// below a pixel at any zoom this map reaches.
const gmst = (julianDay) => (18.697374558 + 24.06570982441908 * (julianDay - 2451545.0)) % 24;

// Ecliptic longitude of the Sun, in degrees. http://en.wikipedia.org/wiki/Position_of_the_Sun
const sunEclipticLongitude = (julianDay) => {
    const n = julianDay - 2451545.0;
    const meanLongitude = (280.460 + 0.9856474 * n) % 360;
    const meanAnomaly = (357.528 + 0.9856003 * n) % 360;

    return meanLongitude
        + 1.915 * Math.sin(meanAnomaly * D2R)
        + 0.02 * Math.sin(2 * meanAnomaly * D2R);
};

// Earth's axial tilt, short-term expression.
const eclipticObliquity = (julianDay) => {
    const t = (julianDay - 2451545.0) / 36525;

    return 23.43929111 - t * (46.836769 / 3600
        - t * (0.0001831 / 3600
            + t * (0.00200340 / 3600
                - t * (0.576e-6 / 3600
                    - t * 4.34e-8 / 3600))));
};

const sunEquatorialPosition = (eclipticLongitude, obliquity) => {
    let alpha = Math.atan(Math.cos(obliquity * D2R) * Math.tan(eclipticLongitude * D2R)) * R2D;
    const delta = Math.asin(Math.sin(obliquity * D2R) * Math.sin(eclipticLongitude * D2R)) * R2D;

    // atan folds everything into one quadrant; restore it from the ecliptic longitude's own.
    alpha += Math.floor(eclipticLongitude / 90) * 90 - Math.floor(alpha / 90) * 90;

    return { alpha, delta };
};

// The night side of the planet as a single GeoJSON polygon, capped at whichever pole is
// currently turned away from the Sun.
export const terminatorPolygon = (date = new Date()) => {
    const julianDay = julian(date);
    const gst = gmst(julianDay);
    const sun = sunEquatorialPosition(sunEclipticLongitude(julianDay), eclipticObliquity(julianDay));

    const pole = sun.delta < 0 ? 90 : -90;
    const ring = [[-LONGITUDE_RANGE / 2, pole]];

    for (let i = 0; i <= LONGITUDE_RANGE * RESOLUTION; i++) {
        const lon = -LONGITUDE_RANGE / 2 + i / RESOLUTION;
        const hourAngle = (gst + lon / 15) * 15 - sun.alpha;

        ring.push([lon, Math.atan(-Math.cos(hourAngle * D2R) / Math.tan(sun.delta * D2R)) * R2D]);
    }

    // Close the ring back along the pole. GeoJSON needs it explicit; Leaflet closed it for us.
    ring.push([LONGITUDE_RANGE / 2, pole], [-LONGITUDE_RANGE / 2, pole]);

    return { type: 'Feature', properties: {}, geometry: { type: 'Polygon', coordinates: [ring] } };
};
