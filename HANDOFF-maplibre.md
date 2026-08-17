# Handoff: migrating the Where2Fly map from Leaflet to MapLibre GL

Status: **plan, not yet implemented.** Everything below was researched against the live
codebase and verified over the network where possible; nothing has been changed on `main`.

Reviewed 2026-08-16: the `primaryAirport` ReferenceError, path options, padding values and
the `Math.sqrt(Math.log(r))` NaN were re-verified line-by-line against `MapDrawRoute.jsx`,
and the package list against `package.json`.

Reviewed 2026-08-17: swapped the hand-ported solar-math terminator for the `crepuscule` npm
package (§9) and RainViewer for Open-Meteo's `weather-map-layer` (§14), per owner request.
Trimmed the provider-comparison sections (library alternatives, tile-provider alternatives,
weather-source alternatives) — those decisions are final, not up for debate (§4). Added §16,
a step-by-step implementation order meant to be prompted one step at a time.

---

## 1. Why

Two problems, one of which is a live bug.

**The route line is geometrically fake.** The distances the app *reports* are correct —
`app/Helpers/helpers.php:31-34` uses haversine, `app/Models/Airport.php:441` uses
`ST_DISTANCE_SPHERE`. What the map *draws* is a cosmetic quadratic Bézier:
`resources/js/components/map/MapDrawRoute.jsx:81` calls `L.curve(['M', a, 'Q', mid, b])`, where
`mid` comes from `calcMidpointLatLng()` (lines 118-149) — planar math on raw degrees, a
hardcoded `3.14/10` bend, and a hand-tuned hemisphere branch faking poleward bulge. A pilot
comparing the drawn line against the `nm` figure beside it is being shown two different stories.

**Mercator misrepresents distance and area** at the zoom levels this app lives at (z3-7),
which is precisely the judgement the app exists to support.

**Live bug:** the antimeridian branch at `MapDrawRoute.jsx:27-52` references `primaryAirport`,
which is *not in scope* in that component — it destructures only `{ airports, setAirports }`.
Every date-line-crossing route throws a `ReferenceError` today. That code is already broken, so
replacing it costs nothing.

**Outcome wanted:** a globe showing real curvature, true great-circle routes, the current dark
aesthetic preserved, zoom-gated terrain relief — at **$0/month**, no API key, no self-hosting,
across ~14k map initialisations per month.

---

## 2. Decisions

| Question | Decision |
|---|---|
| Library | **MapLibre GL JS 6.4.0** (BSD-3; globe projection since v5.0) |
| Basemap | **CARTO `dark-matter-nolabels-gl-style`** — free, no key, same visual identity as today |
| Terrain DEM | **AWS Open Data terrarium** — free, no key |
| Terrain behaviour | **Zoom-gated at z7**; invisible at world/globe view |
| Terminator | **`crepuscule` (npm)** — worker-computed raster tiles, not a hand-ported polygon (§9) |
| Weather overlay | **Open-Meteo `@openmeteo/weather-map-layer`** — no key, separable/opt-in (§14) |
| Camera | **Locked north-up**, no pitch, no bearing |
| Cost | **$0/month**, no account |
| Scope | React map layer only. **No PHP changes, no Blade changes** (except one attribution line) |

### Verified facts (measured, not assumed)

| Thing | Verified value |
|---|---|
| `maplibre-gl` latest | **6.4.0** (2026-07-22). ESM-only, **WebGL2 mandatory**, TS target ES2022 |
| MapLibre JS weight | 567 KB + 482 KB shared = **1.05 MB raw / 276 KB gzip** |
| MapLibre CSS weight | 83 KB raw / **10.6 KB gzip** |
| Current `leaflet` chunk | 209.5 KB raw / **59.7 KB gzip** |
| CARTO style | HTTP 200, 42 KB, `version: 8`, one vector source, 66 layers, **no key** |
| CARTO tilejson | minzoom 0, **maxzoom 14**, and it already carries `attribution: "© CARTO, © OpenStreetMap contributors"` |
| CARTO palette | land `#0e0e0e`, water `#2C353C`, country border `rgba(92,94,94,1)` |
| CARTO glyphs | 200 for `Open Sans Regular/Bold`, `Roboto Regular`, `Noto Sans Regular`. **404 for Work Sans and Kanit** |
| Terrarium DEM | z6 tile 200/90 KB, z13 tile 200/56 KB; `terrarium` encoding present in the dist |
| **`line-trim-offset`** | **NOT in MapLibre** — that's a Mapbox-GL-v3 property. Rules out the "trim" reveal animation |

---

## 3. Feature parity: everything the map does today → MapLibre

Function-level status first — this is the contract the migration must honour. **Everything
ships; two cosmetic cluster niceties are dropped.** The React architecture survives intact:
same component tree shape, same `MapContext`, same `window.*` bridge from Blade, same
`PopupContainer`/`AirportCard` overlay (which was never a Leaflet popup to begin with).

| Current behaviour | MapLibre equivalent | Status |
|---|---|---|
| Dark CARTO `dark_nolabels` basemap | Same style family as vector GL (`dark-matter-nolabels-gl-style`), verified free/keyless | ✅ same look |
| Coloured dots sized by airport type (10/7/5 px) | `circle` layer, data-driven `circle-radius`/`circle-color` | ✅ identical |
| Permanent ICAO labels next to dots | `symbol` layer, `text-field: ['get','icao']`, right-anchored | ✅ (font: Open Sans instead of Work Sans — see risk 6) |
| Labels filtered by zoom on `/search` + home (medium >5, small ≥8) | Layer `minzoom` 6/8 on the label layers | ✅ identical breakpoints |
| Clustering with log-scaled bubbles + counts, radius 60/50/30 by density | Built-in `cluster: true` (Supercluster in the worker) + `interpolate`-on-`ln(point_count)` paint | ✅ identical scaling |
| Cluster click → zoom in | `getClusterExpansionZoom()` + `easeTo` | ✅ |
| Cluster hover convex-hull polygon (`showCoverageOnHover`) | No built-in; rebuildable via `getClusterLeaves` | ❌ dropped in v1 (cosmetic) |
| Spiderfy at max cluster zoom | None — clustering just stops at `clusterMaxZoom` | ❌ dropped (cosmetic) |
| Marker/tooltip click → focus airport, card opens, table row syncs | Layer click handlers on dot + label layers (label clicks preserved — tooltips are `interactive` today) | ✅ |
| Animated route line departure→arrival | Real geodesic + `line-gradient` reveal — **more** correct than today, and fixes a live `ReferenceError` on date-line routes | ✅ upgraded |
| Fly-to-bounds with sidebar-aware pixel padding | `cameraForBounds` with object padding (same 400/350/75/50 values) | ✅ |
| Pan to focused airport | `panTo` (ms-based duration) | ✅ |
| Radar-ping blip on an airport | `maplibregl.Marker` + the existing `.radar-ping` CSS unchanged | ✅ |
| Save map position to localStorage | `moveend` → `getCenter()`; JSON shape `{lng, lat}` is byte-compatible | ✅ no migration |
| Day/night terminator | `crepuscule`'s `CrepusculeLive` — worker-computed raster tiles, auto-refreshing every 5s | ✅ upgraded (today's version computes once on mount and never updates or cleans up) |
| Window bridge (`setAirportsData` etc.) + `mapReady`/`mapFocusAirport` events | Preserved verbatim — zero Blade/PHP changes | ✅ |
| Sentry `ErrorBoundary` + `MapFallback` | Preserved, plus an explicit WebGL2 probe (risk 5) | ✅ hardened |
| Map hidden on mobile (`display:none` < md) | Same CSS, **plus** skip GL construction entirely → saves ~276 KB + all tiles on phones | ✅ improved |
| — (new) | Globe projection, real curvature | ➕ new |
| — (new) | Zoom-gated hillshade terrain | ➕ new |
| — (new) | Weather overlay, user-toggled (§14) | ➕ new |

### Package-level: can the plugins move?

Short answer: **every one of them goes away, and only one needs real work.** Three are replaced
by built-in MapLibre features, one becomes unnecessary entirely, and one (the terminator) swaps
for a small third-party package instead of a hand port.

| Current package | What it does today | MapLibre path | Effort |
|---|---|---|---|
| `leaflet` ^1.9.4 | core | `maplibre-gl` ^6.4.0 | — |
| `react-leaflet` ^5.0.0 | React bindings, `useMap()` | **No replacement needed.** A ~40-line `MapProvider` + `useMapGL()` context. See §7 — `react-map-gl` is deliberately *not* recommended | Small |
| `leaflet.markercluster` ^1.5.3 | clustering | **Built into MapLibre.** GeoJSON sources take `cluster: true` / `clusterRadius` / `clusterMaxZoom` and run Supercluster in the worker. Same algorithm family, no plugin | **None** |
| `react-leaflet-cluster` ^4.1.3 | React wrapper for the above | Gone with it | **None** |
| `@elfalem/leaflet-curve` ^0.9.2 | the Bézier route line | **Not needed** — replaced by real geodesic densification into a plain `line` layer. This plugin only existed because Leaflet has no curve primitive; we no longer want a curve, we want the actual geodesic | **None** (deleted) |
| `@joergdietrich/leaflet.terminator` ^1.1.0 | day/night overlay | **Swap for `crepuscule`** (npm), which computes the same twilight geometry as raster tiles in a worker (§9). Only fall back to a hand port if `crepuscule` doesn't attach cleanly to a raw `maplibregl.Map` | **Small** — one dependency swap, with a documented fallback |
| `@types/leaflet` ^1.9.12 | types | Drop — `maplibre-gl` ships its own | **None** |
| `L.divIcon` markers (`MarkerIcon.jsx`) | coloured dots | `circle` layer with `circle-radius` / `circle-color` expressions | **None** (deleted) |
| `L.divIcon` clusters (`ClusterIcon.jsx`) | cluster bubbles | `circle` + `symbol` layers with `interpolate` on `point_count` | **None** (deleted) |
| Permanent `L.Tooltip` labels | ICAO labels | `symbol` layer, `text-field: ['get','icao']` | **None** (deleted) |
| `MapTooltipZoom.jsx` | CSS-class zoom filtering | layer `minzoom` on three label layers | **None** (deleted) |

**Net: 7 packages removed, 2 added** (`maplibre-gl`, `crepuscule`; `@openmeteo/weather-map-layer`
optionally on top for §14). The dependency footprint gets *simpler*, not more complex — the
reason MapLibre is bigger is that it absorbs all of this into the core.

### Two capabilities that genuinely have no MapLibre equivalent

1. **`showCoverageOnHover`** — the convex-hull polygon (`#46517c` / `#6676b6`) drawn when you
   hover a cluster. Reimplementable via `source.getClusterLeaves(id, Infinity, 0)` + a hull into
   a temp `fill` layer. **Recommend dropping it in v1.**
2. **Spiderfy at max zoom.** MapLibre simply stops clustering at `clusterMaxZoom`. Accept.

---

## 4. Decisions, not up for debate

MapLibre GL JS was chosen over Mapbox GL v3 (proprietary since v2, needs an account/token),
Cesium (multi-MB bundle, would mean rebuilding the CARTO look from scratch), deck.gl (sits on
top of a basemap library, doesn't replace one), Globe.gl/three-globe (not a real map — no
vector tiles, no zoom-dependent labels, no clustering), OpenLayers (no WebGL globe), and
ArcGIS/Google Maps (billing risk on a site that reloads the map on every search). CARTO's
`dark-matter-nolabels-gl-style` was chosen as the basemap over OpenFreeMap/MapTiler/Stadia for
being free, keyless, and the exact vector twin of today's raster style (§2). If you want the
full comparison re-run — new library releases, pricing changes — ask; it isn't reproduced here.
The Leaflet-based fallback if MapLibre itself hits a hard blocker is in §15.

---

## 5. Architecture: GL layers, not DOM markers

The single biggest win, independent of the globe. `Map.jsx:200-208` sets `clusterRadius` to 60
for **≥1000 airports** — the logged-in default view really does put 1000+ `DivIcon`s and 1000+
permanent tooltips in the DOM. Replace all of it with one GeoJSON source and GPU-drawn layers.

`MapTooltipZoom.jsx` exists *only* because CSS class toggling was the cheapest way to avoid
re-rendering 1000 React markers on zoom. `MapMarker.jsx`'s `memo`, its `useMemo(icon)`, and the
`mapContextValue` memo at `Map.jsx:215-225` are all scaffolding to keep React out of the marker
path. All of it becomes unnecessary.

### Source

Built from the existing `{icao: {id, icao, lat, lon, color, type}}` payload — **no PHP change**:

```js
// resources/js/components/utils/airportsGeoJson.js
const TYPE_RADIUS = { large_airport: 5, medium_airport: 3.5, small_airport: 2.5 }; // Leaflet used 10/7/5 px diameter

// SearchController.php:309-313 omits `color` for the primary airport and sends the
// literal 'grey' for candidates; MarkerIcon.jsx fell back to #ddb81c on null.
const normalizeColor = (c) => (!c ? '#ddb81c' : c === 'grey' ? '#808080' : c);

export const airportsToGeoJson = (airports) => ({
    type: 'FeatureCollection',
    features: Object.values(airports).map((a) => ({
        type: 'Feature',
        geometry: { type: 'Point', coordinates: [Number(a.lon), Number(a.lat)] },
        properties: {
            id: a.id,
            icao: a.icao,
            type: a.type ?? 'large_airport',
            color: normalizeColor(a.color),
            r: TYPE_RADIUS[a.type] ?? 5,
        },
    })),
});
```

```js
map.addSource('airports', {
    type: 'geojson',
    data: airportsToGeoJson(airports),
    cluster,          // from window.setCluster
    clusterRadius,    // 60 / 50 / 30 — same buckets as Map.jsx:201-207
    clusterMaxZoom: 12,
});
```

> **Critical constraint:** `cluster` and `clusterRadius` are **construction-time only** —
> `setData()` cannot change them. Use two effects: one keyed on `[map, cluster, clusterRadius]`
> that removes and re-adds source + layers, and one keyed on `[map, airports]` that only calls
> `setData()`. Keep `clusterRadius` bucketed (not raw count) so the `api.mapdata.icao` fetch at
> `Map.jsx:143`, which appends a single airport, can't flip the bucket and force a rebuild.

Also: today `{clusterRadius && (...)}` at `Map.jsx:243` gates marker rendering on data arriving.
In the new design **always create the source** with an empty `FeatureCollection` — one less race.

### Layers (insert above the CARTO style, no `beforeId`)

```
airports-hit           circle  radius 9, opacity 0        <- hit target only
airports-clusters      circle  filter ['has','point_count']
airports-cluster-count symbol
airports-dots          circle  filter ['!',['has','point_count']]
airports-label-large   symbol  minzoom 0
airports-label-medium  symbol  minzoom 6
airports-label-small   symbol  minzoom 8
airports-label-pinned  symbol  minzoom 0, text-allow-overlap
```

`airports-hit` is a real UX win: a `small_airport` is a 5×5 px click target today. A transparent
9 px circle above it fixes that, and `queryRenderedFeatures` still returns features from a fully
transparent layer (querying uses geometry, not alpha).

```js
{
  id: 'airports-dots', type: 'circle', source: 'airports',
  filter: ['!', ['has', 'point_count']],
  paint: {
    'circle-radius': ['get', 'r'],
    'circle-color': ['case',
        ['==', ['get', 'icao'], focusIcao ?? ''], '#ddb81c',
        ['to-color', ['get', 'color']]],
  },
}
```

> `['to-color', ...]` is **required**. A `['get']` returns a *string*, and MapLibre will not
> implicitly coerce a string-typed expression to a colour. A literal `"#fff"` in style JSON is
> fine; an expression result is not. Easy foot-gun, silent-ish failure.

**Zoom/label rules.** Note the current behaviour is route-conditional — `MapTooltipZoom.jsx:12-14`
+ `map.scss:58-69` only filter on `route().current('search')` or the home page, **not** on `top`,
`scenery`, or `search.routes`. Leaflet zoom is integer, so `zoom > 5` means 6 and up; MapLibre has
fractional zoom, so use `minzoom: 6` / `minzoom: 8`, and `0` for all three when filtering is off.

Use layer `minzoom`, **not** a `['zoom']` filter: `['zoom']` inside a filter is evaluated at
tile-build time on integer zooms and forces re-tiling, whereas layer `minzoom` is a free
renderer-level cutoff that also correctly frees collision space.

### State → styling

| State | Mechanism |
|---|---|
| `focusAirport` | `setPaintProperty` on the dot + label layers, plus `setFilter` on the pinned layer. Paint updates rebuild vertex arrays on the **main thread only** — no worker round-trip, no re-tile |
| `primaryAirport` | Same pinned-layer filter. Its gold colour is already free: `SearchController.php:309-313` omits `color` for the primary → `normalizeColor(undefined)` → `#ddb81c` |
| `highlightedAircrafts` | **No map work at all.** Verified by grep: read only in `AirportCard.jsx:52` and `FlightsCard.jsx:28` as an API request-body field. It never touched a marker. Leave it in `MapContext` untouched |

`feature-state` + `promoteId` was considered and rejected for v1: feature-state on a *clustered*
GeoJSON source is unreliable, because Supercluster regenerates features per zoom and states get
dropped. Paint expressions have no such lifecycle. Note it as the escape hatch if 1000-airport
repaints ever measure badly.

### Click handling

```js
const AIRPORT_LAYERS = ['airports-hit', 'airports-label-large', 'airports-label-medium',
                        'airports-label-small', 'airports-label-pinned'];

map.on('click', AIRPORT_LAYERS, (e) => setFocusAirport(e.features[0].properties.icao));
map.on('click', 'airports-clusters', async (e) => {
    const f = e.features[0];
    const zoom = await map.getSource('airports').getClusterExpansionZoom(f.properties.cluster_id);
    map.easeTo({ center: f.geometry.coordinates, zoom });
});
```

Including the label layers preserves a subtle current behaviour: `MapMarker.jsx:31` sets
`interactive={true}` on the tooltip, forwarding clicks on the ICAO *text* to the marker.
`getClusterExpansionZoom` returns a **Promise** in v5+, not a callback.

### Clusters

`ClusterIcon.jsx`'s log scale (min 2rem, max 3.75rem, over `ln(2)..ln(100)`) ports directly:

```js
const rem = parseFloat(getComputedStyle(document.documentElement).fontSize) || 16;
const R = (n) => (n * rem) / 2;   // rem diameter -> px radius

'circle-radius': ['interpolate', ['linear'], ['ln', ['get','point_count']],
                  Math.log(2), R(2), Math.log(100), R(3.75)],
'circle-color': isDefaultView() ? '#2f3549' : '#ddb81c',
```

`interpolate` clamps outside its stop range, which reproduces `Math.min(1, Math.max(0, ...))` for
free.

---

## 6. Geodesic route

### 6.1 Densification — write it locally, don't add a dependency

`@turf/great-circle` pulls `@turf/helpers` + `@turf/invariant` + arc.js and, importantly,
**splits at the antimeridian into a MultiLineString by default** — the opposite of what we want
for globe rendering and for `fitBounds`. `arc.js` has the same opinion. The maths is 20 lines.

```js
// resources/js/components/utils/geodesic.js
const D2R = Math.PI / 180, R2D = 180 / Math.PI;

// Spherical slerp. Emits CONTINUOUS (unwrapped) longitudes: returned lons may exceed
// ±180 so both globe and the mercator fallback draw one unbroken line.
export function greatCircle([lon1, lat1], [lon2, lat2], segments) {
    const p1 = lat1 * D2R, l1 = lon1 * D2R, p2 = lat2 * D2R, l2 = lon2 * D2R;

    const d = 2 * Math.asin(Math.sqrt(
        Math.sin((p2 - p1) / 2) ** 2 +
        Math.cos(p1) * Math.cos(p2) * Math.sin((l2 - l1) / 2) ** 2));

    if (!Number.isFinite(d) || d < 1e-9) return [[lon1, lat1], [lon2, lat2]];

    // Near-antipodal: the great circle is undefined, sin(d)->0. Never happens for real
    // airport pairs, but a NaN geometry silently blanks the whole layer.
    if (Math.abs(Math.PI - d) < 1e-6) return [[lon1, lat1], [lon2, lat2]];

    const n = segments ?? Math.min(256, Math.max(32, Math.ceil(d * R2D)));  // ~1 pt/deg
    const out = [];
    let prevLon = lon1;

    for (let i = 0; i <= n; i++) {
        const f = i / n;
        const A = Math.sin((1 - f) * d) / Math.sin(d);
        const B = Math.sin(f * d) / Math.sin(d);
        const x = A * Math.cos(p1) * Math.cos(l1) + B * Math.cos(p2) * Math.cos(l2);
        const y = A * Math.cos(p1) * Math.sin(l1) + B * Math.cos(p2) * Math.sin(l2);
        const z = A * Math.sin(p1) + B * Math.sin(p2);

        const lat = Math.atan2(z, Math.hypot(x, y)) * R2D;
        let lon = Math.atan2(y, x) * R2D;

        // Unwrap: keep each step within ±180 of the previous point.
        while (lon - prevLon > 180) lon -= 360;
        while (lon - prevLon < -180) lon += 360;

        out.push([lon, lat]);
        prevLon = lon;
    }
    return out;
}
```

~1 point per degree (max 256) gives ≤111 km segments; the sagitta error of a chord that long is
~0.24 km — sub-pixel at any zoom this map reaches.

**Why unwrapped longitudes should work in both projections:** MapLibre's GeoJSON source runs
`@maplibre/geojson-vt`, whose `wrap()` step clips features into the `x ∈ [-1-buf, buf]` and
`x ∈ [1-buf, 2+buf]` bands and shifts them by ±1 world. So RJTT(139.78) → KSFO(237.63)
materialises as two rendered pieces at 139.78→180 and −180→−122.37, which is correct under
Mercator with `renderWorldCopies`. Under globe the sphere is continuous so it renders as one arc.
**This is the single claim in this document most in need of empirical confirmation** (§11.4).
Fallback if geojson-vt misbehaves: split the point array at every ±180 crossing into a
`MultiLineString` ourselves — 10 extra lines, drawn identically.

### 6.2 Draw-on animation

`line-dasharray` cannot be smoothly animated, and **`line-trim-offset` does not exist in
MapLibre** (verified — it's Mapbox GL v3 only). Use a `line-gradient` reveal on a source with
`lineMetrics: true`:

```js
const EASE = (t) => (t < 0.5 ? 4*t*t*t : 1 - (-2*t + 2)**3 / 2);

// Original: Math.sqrt(Math.log(r)) * 200 with r in planar degrees — NaN for r < 1.
const duration = Math.sqrt(Math.log(Math.max(arcDeg, Math.E))) * 200;   // >= 200ms

let raf, t0;
const step = (ts) => {
    t0 ??= ts;
    const p = Math.min(1, EASE((ts - t0) / duration));
    if (p >= 1) { map.setPaintProperty('route-line', 'line-gradient', undefined); return; }
    const a = Math.min(Math.max(p, 0.001), 0.998), b = a + 0.001;
    map.setPaintProperty('route-line', 'line-gradient', [
        'interpolate', ['linear'], ['line-progress'],
        0, '#ddb81c', a, '#ddb81c', b, 'rgba(221,184,28,0)', 1, 'rgba(221,184,28,0)',
    ]);
    raf = requestAnimationFrame(step);
};
```

- Stops must be **strictly increasing** — hence the clamp; an out-of-order stop throws a
  style-spec validation error and the paint update is silently dropped.
- Resetting to `undefined` at the end falls back to flat `line-color`, so steady state is free.
- Pure paint update per frame (a 256 px gradient re-upload) — no geometry churn, no worker
  traffic. This replaces the two-pass SVG-then-canvas hack at `MapDrawRoute.jsx:96-105` entirely.

### 6.3 Camera framing

`MapDrawRoute.jsx:84-88` uses Leaflet's `paddingTopLeft`/`paddingBottomRight`:

| viewport | Leaflet | MapLibre |
|---|---|---|
| `> 1920` | `[400,350]` / `[75,50]` | `padding: { left: 400, top: 350, right: 75, bottom: 50 }` |
| `> 767` | `[50,350]` / `[50,50]` | `padding: { left: 50, top: 350, right: 50, bottom: 50 }` |
| `<= 767` | no fly at all | keep — `.map` is `display:none` below `md` |

MapLibre's `fitBounds` has `maxZoom` but **no `minZoom`**, so the `minZoom: 3` clamp is manual:

```js
const cam = map.cameraForBounds(bounds, { padding, maxZoom: 7 });
if (cam) map.flyTo({ ...cam, zoom: Math.max(3, cam.zoom), duration: 350 });
map.once('moveend', startRevealAnimation);
```

Guard the padding — MapLibre misbehaves if `left+right >= width`. Clamp defensively.

### 6.4 Bounds helper — fixes another existing bug

`MapBound.jsx` and `Map.jsx:193-197` build bounds by pushing raw `[lat, lon]`. For any set
spanning the date line (RJTT + KSFO) that yields a 262°-wide box across the Atlantic, and the
camera flies the wrong way around the planet. Add a `boundsFromCoordinates()` that sorts
longitudes, finds the largest gap, and returns its complement (west may be < −180). Use it in
both `MapBound` and the route. **This is a real bug fix, not just a port.**

---

## 7. Globe setup

```js
const map = new maplibregl.Map({
    container: el,
    style: 'https://basemaps.cartocdn.com/gl/dark-matter-nolabels-gl-style/style.json',
    center: getInitMapPosition(),   // NOTE: [lng, lat] — Leaflet was [lat, lng]
    zoom: 4,
    minZoom: 0,                     // was 3
    maxZoom: 17,
    attributionControl: false,      // we add a configured one, §10
    dragRotate: false,
    pitchWithRotate: false,
    touchPitch: false,
    // No drag inertia (owner preference). maxSpeed: 0 is the whole trick; linearity MUST stay
    // > 0 — inertia duration is speed/(deceleration*linearity), and 0/0 = NaN wedges panning.
    dragPan: { linearity: 0.3, maxSpeed: 0, deceleration: 2500 },
    // maxBounds: DROPPED
});
map.touchZoomRotate.disableRotation();
map.on('style.load', () => { map.setProjection({ type: 'globe' }); map.setSky({ /* below */ }); });
```

> **`center` is the single most likely silent bug in this migration.** `getInitMapPosition()`
> (`Map.jsx:37-66`) returns `[lat, lng]` in six branches plus a localStorage read. Every one must
> be flipped — while keeping the stored `{lat, lng}` payload shape for backwards compatibility.

**Drop `maxBounds`.** `[[-85,-360],[85,360]]` was a Leaflet-ism allowing ~3 world copies while
stopping vertical over-pan. Per the MapLibre globe developer guide, *"globe transform currently
does not support constraining the map's center"* — it's documented as unreliable around the z12
transition. Dead code at best, jitter at worst. The `±360` world-copy trick goes too: there are
no world copies on a globe, and `renderWorldCopies: true` already handles Mercator.

**`minZoom` 3 → 0.** At 3 you can never see the whole planet, which defeats the point. Route
framing keeps its own `Math.max(3, …)` clamp so search behaviour is unchanged.

**Globe→Mercator transition** is automatic at ~z12, blended, not configurable. Everything above
z12 is effectively the old Mercator map — which is exactly why the antimeridian handling has to
be right in *both*.

**Poles:** Web Mercator tiles stop at ±85.05°, so a MapLibre globe has a literal hole at each
pole showing the background colour. Against `#000` this reads as "the pole is in shadow" rather
than a bug — but it *is* visible on a polar route. No fix short of a custom polar-cap layer.

**Sky / atmosphere.** The CARTO style has no `sky` block, and land is `#0e0e0e` on a `#000` page —
without an atmosphere the globe's limb is invisible.

```js
map.setSky({
    'sky-color': '#05070c',
    'horizon-color': '#2C353C',   // == CARTO water colour, keeps the palette honest
    'fog-color': '#0e0e0e',
    'sky-horizon-blend': 0.5,
    'horizon-fog-blend': 0.6,
    'atmosphere-blend': ['interpolate', ['linear'], ['zoom'], 0, 0.6, 5, 0.4, 7, 0],
});
```

Fading atmosphere to 0 by z7 means the search/results views (z3-7) look essentially like today.
This is the main taste knob — expect to iterate from screenshots. `sky` is marked *experimental*
in the style spec; omitting it still gives a working, flatter-looking globe.

**Pitch/rotate stay disabled.** Three things assume north-up-and-flat: the absolutely-positioned
`.popup-container` / `.hint` / `.feedback` overlays (`map.scss:116-141, 225-266`); the
`mapFocusAirport` event driving table-row scroll sync (`top.blade.php:143`,
`airports.blade.php:323`); and the whole "route bulges north" intuition.

### React integration: raw `maplibre-gl`, **not** `react-map-gl`

Every one of the ten map components is already an imperative `useMap()` + `useEffect` shim that
returns `null` — there is no declarative tree for `react-map-gl`'s `<Source>/<Layer>` JSX to buy
you. And `react-map-gl@8`'s peer range `maplibre-gl: >=1.13.0` is nominal, not evidence of v6
support; v6 is ESM-only, WebGL2-only, and changed `GeoJSONSource.setData`. Coupling a production
map to a wrapper that may lag a two-month-old major is the wrong trade for ~10 components.

```jsx
export function MapProvider({ children }) {
    const ref = useRef(null);
    const [map, setMap] = useState(null);   // null until 'style.load'

    useEffect(() => {
        const m = new maplibregl.Map({ container: ref.current, /* §7 */ });
        m.on('style.load', () => { m.setProjection({type:'globe'}); m.setSky({...}); setMap(m); });
        return () => m.remove();
    }, []);

    return (
        <MapGLContext.Provider value={map}>
            <div className="map" ref={ref} />
            {map && children}
        </MapGLContext.Provider>
    );
}
```

> **`window.dispatchEvent(new Event('mapReady'))` must stay in `Map`'s mount effect**, not move
> to `style.load`. Blade handlers call `setAirportsData(...)` synchronously in that listener;
> that's React state, which lands regardless of style readiness, and the `[map, airports]` effect
> flushes it the moment `map` becomes non-null. Moving it adds ~200 ms of dead time and a new
> failure mode if the style request fails.

**Mobile:** `.map` is `display:none` below `md`. Skip map construction when the container computes
to `display: none` — the bridge still exists, `map` stays `null`, all effects no-op, and you save
the entire GL init plus every tile request on phones. Re-check on a `matchMedia` change.

---

## 8. Terrain — zoom-gated hillshade

**Honest framing:** with pitch locked at 0°, `setTerrain()` is nearly invisible — 3D relief only
reads when the camera tilts. The mechanism that works at pitch 0 is a **`hillshade` layer**. So
this plan ships hillshade and deliberately omits `setTerrain()`.

```js
map.addSource('dem', {
    type: 'raster-dem', tileSize: 256, maxzoom: 13, encoding: 'terrarium',
    tiles: ['https://s3.amazonaws.com/elevation-tiles-prod/terrarium/{z}/{x}/{y}.png'],
    attribution: '<a href="https://github.com/tilezen/joerd/blob/master/docs/attribution.md">Tilezen Joerd</a>',
});
map.addLayer({
    id: 'hillshade', type: 'hillshade', source: 'dem', minzoom: 7,
    paint: {
        'hillshade-exaggeration': ['interpolate', ['linear'], ['zoom'], 7, 0, 9, 0.35],
        'hillshade-shadow-color': '#000000',
        'hillshade-highlight-color': '#3a4048',
        'hillshade-accent-color': '#000000',
    },
}, 'boundary_country_inner');
```

`minzoom: 7` plus exaggeration ramping from 0 is what protects "keep the dark look exactly":
**no DEM requests and no visual change below z7.** Most of the 14k monthly inits sit at z2-6, so
the added cost is close to zero. When it *is* on, a 1400×1000 viewport at z8 pulls ~24 tiles
≈ 1.5 MB — which is the other reason for the gate.

**If you later unlock pitch**, add `map.setTerrain({ source: 'dem', exaggeration: 1.2 })` and
enable `dragRotate` at the same time. Anything above ~1.5 exaggeration looks like a video game on
a dark basemap.

**Globe + terrain compatibility is not guaranteed by the docs**, though working demos exist. Since
hillshade needs no `setTerrain`, this plan avoids the question entirely. If you do add terrain
later and it tears, the mitigation is one line: `map.setProjection({type:'mercator'})` while on.

---

## 9. Terminator — `crepuscule`, not a hand port

Package: **`crepuscule`** (npm, v1.0.0 — `npm install crepuscule`). Renders the day/night
boundary as dynamically-generated **raster tiles** — a Web Worker (`tile-worker.js`) computes
the twilight math per z/x/y, z0-22, served through a custom protocol registered on the map. Not
a GeoJSON polygon, so the earlier plan of a `fill` layer is unnecessary — but keep the fallback
below.

```js
import { CrepusculeLive } from 'crepuscule';

let terminator;
map.on('style.load', () => {
    terminator = new CrepusculeLive(map, {
        color: [0, 0, 17],   // default; near-black, matches CARTO's palette
        opacity: 0.3,        // matches today's leaflet.terminator opacity
    });
});
// cleanup: terminator?.unmount();
```

`CrepusculeLive` auto-refreshes on its own timer (README states every 5s, on a worker) — no
manual `setInterval` needed on our side, which also fixes today's staleness bug for free.
Methods: `setDate()`, `setOpacity()`, `show()`/`hide()` (accept `{duration, delay}`), `update()`,
`unmount()`; `CrepusculeLive` adds `start()`/`stop()`.

**Ordering:** each instance's layer id is auto-generated (`crepuscule_layer_<uuid>`), so there's
no fixed id to pass as `beforeId`. Newly added layers stack on top of whatever exists, so
instantiate `CrepusculeLive` **first**, in `style.load`, before `MapAirportLayers`/`MapTerrain`
add anything — that puts it at the bottom of the stack, which is where it belongs.

**Two risks worth resolving in the first hour of implementation, not discovering later:**

1. **Its `package.json` peer-dependency is `@maptiler/sdk ^1.1.1`, not `maplibre-gl`.** Every
   official example (`examples/index.html`) instantiates against a `maptilersdk.Map`, none
   against a raw `maplibregl.Map`. Reading the source (`src/crepuscule.ts`, ~7 KB) shows the only
   map calls it makes are `addSource`/`getSource`/`addLayer`/`setPaintProperty`/`removeLayer`/
   `removeSource`/`once`/`loaded`/`triggerRepaint`/`addProtocol` — all standard `maplibregl.Map`
   methods that MapTiler SDK's `Map` class merely subclasses, so it should work unmodified.
   **Verify `addProtocol` specifically** (imported from `@maptiler/sdk`, which re-exports
   MapLibre's) against our raw `maplibregl.Map` instance before relying on it — this is the one
   call most likely to diverge between the two SDKs.
2. **The repo is stale:** last pushed 2024-03-11, 11 GitHub stars, `maplibre-gl` doesn't appear
   in `package.json` at all. If the npm package doesn't attach cleanly to v6, the source is small
   enough to vendor directly — copy `src/crepuscule.ts` + `src/tile-worker.js` into
   `resources/js/components/utils/`, swap the `@maptiler/sdk` type imports for `maplibre-gl`'s.
   That's a cheaper fallback than the from-scratch solar-math port below. License is a custom
   `LICENSE.md` (GitHub reports it as "Other", not a recognized SPDX id) — read it before
   vendoring or shipping.

**Escape hatch if both of the above fail** — the original hand port, GeoJSON `fill` layer below
the airport layers:

```js
map.addLayer({
    id: 'terminator', type: 'fill', source: 'terminator',
    paint: { 'fill-color': '#000000', 'fill-opacity': 0.3, 'fill-antialias': false },
}, 'airports-hit');
```

fed by ~60 lines of solar maths (`julian`, `GMST`, sun ecliptic/equatorial position, hour angle,
latitude-of-terminator — the same maths any `leaflet.terminator`-style implementation uses)
emitting a GeoJSON `Polygon` in `[lon, lat]`, refreshed on a manual `setInterval(..., 60_000)`
with a `longitudeRange` of 360 (not Leaflet's 720, which existed only to cover world copies).
Same mount-order footgun as above: guard with
`map.getLayer('airports-hit') ? addLayer(spec, 'airports-hit') : addLayer(spec)`.

---

## 10. Attribution

Current state: Leaflet's control is off (`Map.jsx:233`) and `footer.blade.php:10` hardcodes
*"Map powered by Leaflet & CartoDB"* — which **omits OpenStreetMap entirely**. That's an existing
ODbL attribution gap, worth fixing as part of this.

**Use both** the MapLibre control and a footer line:

```js
map.addControl(new maplibregl.AttributionControl({ compact: true }), 'bottom-right');
```

MapLibre auto-aggregates the `attribution` field from every source. CARTO's tilejson already ships
`© CARTO, © OpenStreetMap contributors`, and the `dem` source adds Tilezen only when terrain is on
— so it stays legally correct as sources change, with no manual upkeep. Needs a dark-theme
override in `map.scss` (`.maplibregl-ctrl-attrib` is white-on-white over `#0e0e0e`).

Keep the footer line too, because `.map` is `display:none` on mobile and the footer is the only
credit those users see:

```blade
Map powered by <a href="https://maplibre.org/">MapLibre</a>
&amp; <a href="https://carto.com/attribution">CARTO</a>,
&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors
```

MapLibre itself is BSD-3 and needs no attribution; CARTO's terms and OSM's ODbL do.

---

## 11. Build, bundle, and file-by-file

### 11.1 Dynamic import gate

`resources/js/app.js:7` statically imports `./components/Map`, so Leaflet is a hard dependency of
the entry graph and gets modulepreloaded on **every** page. Change to:

```js
if (document.getElementById('map')) {
    import('./components/Map');
}
```

Safe with the `mapReady` contract: `@yield('js')` sits at `layouts/app.blade.php:57`, so the
inline listener registers during HTML parse, while `@vite('resources/js/app.js')` at
`layouts/header.blade.php:43` is deferred. The listener is always registered first.

Honest sizing: only 2 of 21 views use `appStatic`, so the page-count win is small. The real wins
are (a) the map chunk leaves the critical module graph, and (b) it composes with the mobile gate
in §7, which is where the big saving is.

### 11.2 `vite.config.mjs`

```js
groups: [
    { name: 'maplibre', test: /node_modules\/(maplibre-gl|@maplibre|pbf|earcut|kdbush|potpack|gl-matrix|tinyqueue|quickselect|murmurhash-js|@mapbox)\// },
    { name: 'react',    test: /node_modules\/(react|react-dom|scheduler)\// },
    { name: 'vendor',   test: /node_modules/ },
],
```

A naive `/node_modules\/.*maplibre/` rename would leave `pbf`, `earcut`, `gl-matrix`, `@mapbox/*`
in `vendor`, dragging map-only code back into the always-loaded chunk.

### 11.3 CSS placement

Do **not** put `maplibre-gl.css` in `app.scss`. Import it at the top of the dynamically-imported
map module so Rolldown emits it as a chunk-scoped asset. `app.css` is already 402 KB raw / 80 KB
gzip; adding 10.6 KB gzip of control chrome to the render-blocking stylesheet is wrong when the
map is lazy. Remove `app.scss:47-48`. *Verify* rolldown-vite 8 actually emits + injects dynamic
chunk CSS; fall back to the `app.scss` import if not.

### 11.4 Expected delta (measured, gzip)

| | before | after |
|---|---|---|
| map JS chunk | 59.7 KB (`leaflet`) | **~276 KB** (`maplibre`) |
| map CSS | ~4.2 KB inside `app.css` | ~10.6 KB separate chunk |
| critical path, map pages | 160 KB **+ 59.7 KB preloaded leaflet** | 160 KB; map fetched off the critical path |
| `appStatic` pages | −59.7 KB | −59.7 KB, no maplibre at all |
| mobile (< 768 px) | pays full map cost today | **−276 KB JS, −10.6 KB CSS, −all tiles** |

Net **+216 KB gzip**, moved off the blocking path. `crepuscule` and (if shipped)
`@openmeteo/weather-map-layer` are both small single-file-ish packages, not separately measured
here — check their real weight in the build output once added rather than assuming.
`vendor` is 96 KB gzip and mostly `@sentry/react` — out of scope, but the next-biggest lever if
weight becomes a concern.

### 11.5 File-by-file

**Rewrite**

| File | Change |
|---|---|
| `resources/js/components/Map.jsx` | Keep `isDefaultView`, the whole bridge block (85-114), the auth/list fetches (103-133), the `focusAirport` effect (136-185), the bounds/clusterRadius effect (188-210), `mapContextValue`, `PopupContainer`, `MapFallback`, the `createRoot` mount. Replace `<MapContainer>/<TileLayer>/<MarkerClusterGroup>` with `<MapProvider>`. **Flip `getInitMapPosition()` to `[lng, lat]` in all 8 return paths** |
| `map/MapBound.jsx` | `fitBounds(boundsFromCoordinates(coords), { padding: 50, animate: false })` |
| `map/MapPan.jsx` | `panTo([lng, lat], { duration: 500 })` — ms, not seconds |
| `map/MapPing.jsx` | `new maplibregl.Marker({ element, anchor:'center' })`. `.radar-ping` CSS unchanged — its `::before` at `top/left:-24px` on a 0×0 element behaves identically under `.maplibregl-marker{position:absolute}`. Keep the 1800 ms teardown |
| `map/MapSaveView.jsx` | `map.on('moveend', …)`. `JSON.stringify(map.getCenter())` yields `{"lng":…,"lat":…}` — **byte-compatible with existing `localStorage.mapPosition`**, no migration needed |
| `map/MapTerminator.jsx` | Swap `@joergdietrich/leaflet.terminator` for `crepuscule`'s `CrepusculeLive` (§9). Fall back to the GeoJSON `fill` layer only if the risks in §9 don't resolve cleanly |
| `sass/app.scss` | Drop lines 47-48 |
| `sass/map.scss` | Keep `.map`, `.map-error`, `.radar-ping` + keyframes, `.popup-*`, `.hint`, `.feedback`. Delete `.leaflet-div-icon`, `.leaflet-tooltip*`, the `.map.tt-filter` block (57-69), `.leaflet-marker-icon.marker-cluster` (97-114). Add `.maplibregl-map{background:#000}`, `.maplibregl-canvas:focus{outline:none}`, dark `.maplibregl-ctrl-attrib` |
| `resources/js/app.js` | Gate the import on `#map` |
| `vite.config.mjs` | `codeSplitting.groups` |
| `views/layouts/footer.blade.php` | Line 10 attribution |
| `package.json` | −7 Leaflet packages, +`maplibre-gl@^6.4.0`, +`crepuscule`; optionally +`@openmeteo/weather-map-layer` for §14 (separate step, separate PR is fine) |

**Delete:** `map/MapMarker.jsx`, `map/MapMarkerGroup.jsx`, `map/MapTooltipZoom.jsx`,
`map/MapDrawRoute.jsx`, `utils/MarkerIcon.jsx`, `utils/ClusterIcon.jsx`

**New:** `map/MapProvider.jsx`, `context/MapGLContext.js`, `map/mapConfig.js`,
`map/MapAirportLayers.jsx`, `map/MapRoute.jsx`, `map/MapTerrain.jsx`, `utils/geodesic.js`,
`utils/airportsGeoJson.js`. (`utils/solarTerminator.js` only if the `crepuscule` fallback in §9
is needed.)

**Untouched (verified):** `MapContext.js`, `PopupContainer.jsx`, `AirportCard.jsx`,
`FlightsCard.jsx`, `SceneryCard.jsx`, all four Blade consumers, `parts/map.blade.php`, and
**every PHP file** — `MapHelper.php`, `SearchController.php`, `helpers.php`,
`CalculationHelper.php`, `Airport.php`.

---

## 12. Verification

No JS tests exist and PHPUnit doesn't touch any of this, so verification is the `run-where2fly`
skill plus eyes. Run `php artisan test` once at the end purely as a regression check that no PHP
was disturbed.

**Take baseline screenshots of all six views on the current Leaflet build first.** Every "does it
still look right" judgement below is a diff against those.

### Page matrix

| # | URL | What must be true |
|---|---|---|
| 1 | `/` | Globe with a faint limb; land `#0e0e0e`, water `#2C353C`; terminator at 30% black; stored `mapPosition` (or Berlin) at z4; no airports logged out; compact ⓘ bottom-right |
| 2 | `/top` | Centre `[-35.4521, 45.14777]` z4; **gold** clusters (`isDefaultView()` false); labels at **all** zooms (no `tt-filter` on this route) |
| 3 | `/top/EU` | Centre `[15.2551, 54.5260]` z4 |
| 4 | `/search?icao=EGLL&direction=departure&…` | **No** clusters (`setCluster(false)`); EGLL gold (no `color` → `#ddb81c` fallback), candidates `#808080`; medium labels only at z≥6, small only at z≥8 |
| 5 | `/scenery` | Renders; `setAirportsData` lands; `mapSaveView` persists on pan |
| 6 | `/feedback` | **`maplibre` chunk NOT requested** — `performance.getEntriesByType('resource').filter(r=>r.name.includes('maplibre')).length` → `0` |

### The two geometry proofs

`/search/routes` needs real `flights` rows the dev DB may lack. Drive the bridge directly instead
— deterministic, no data needed.

**A. Antimeridian (RJTT → KSFO).** This throws a `ReferenceError` today, so anything rendered at
all is already a fix.

```
eval window.setAirportsData({RJTT:{id:1,icao:'RJTT',lat:35.5533,lon:139.7811,type:'large_airport',color:'#ddb81c'},KSFO:{id:2,icao:'KSFO',lat:37.6188,lon:-122.3750,type:'large_airport',color:'grey'}}); window.setDrawRoute(['RJTT','KSFO']);
```

Expect **one continuous arc across the Pacific**, bulging north toward the Aleutians. Fail modes:
a line the wrong way round across Europe/Africa; a line stopping dead at 180°; a seam gap; the
camera flying to the Atlantic. Then `eval map.setZoom(13)` (past the globe→mercator transition)
and confirm it's still unbroken.

**B. High-latitude antimeridian (ENGM → PAJN).** Should arc over the Arctic. Confirms unwrapping
survives high latitude, and shows the ±85° polar hole (expected, not a bug).

**C. Polar bulge (EGLL → RJTT).** Must arc conspicuously **north over Siberia**. This is the money
shot — compare against baseline, where the hardcoded `3.14/10` theta offset produces a cosmetic
bulge that is not the great circle.

**D. Short-route sanity (EHAM → EHRD).** Confirms the `Math.log(r)` NaN guard: today `r < 1°`
gives `sqrt(log(0.8))` = NaN. Line must still draw, with a 200 ms floor.

### Interaction checks (on view #4)

`eval window.setFocusAirport('LFPG')` → card opens (proves `mapFocusAirport` + `api.airport.show`);
dot turns `#ddb81c` and its label shows regardless of zoom; table row gains `.active` and scrolls
(`airports.blade.php:323`); route curve draws EGLL→LFPG. Then click a dot **and its ICAO text** on
the canvas to prove hit-testing parity with `interactive` tooltips.
`eval window.pingAirport('LFPG')` → ring expands and vanishes after 1.8 s.

### Console hygiene

`console --errors` on every view. Known-harmless: umami 400s, `GET /api/user/authenticated 400`
when logged out. Anything else is a real failure — especially style-spec validation errors
("Expected color but found string", "stops must be strictly ascending"), `Failed to load Worker`,
and **glyph 404s**, which produce *silently missing labels* rather than a visible error. Grep the
network log for `fonts/` explicitly.

---

## 13. Risks, ranked

1. **`line-gradient` under globe projection.** Globe subdivides line geometry before rendering;
   whether `line-progress` stays monotonic post-subdivision is unconfirmed. Mitigation: fade in via
   `line-opacity`, or progressive `setData`.
2. **Unwrapped longitudes through `@maplibre/geojson-vt`.** Reasoned through from the library's
   `wrap()` clip-and-shift, but not run. Mitigation: split to `MultiLineString` at ±180 ourselves
   (~10 lines).
3. **`cameraForBounds` accuracy under globe.** Newer, less battle-tested, open issues around the
   z12 transition. The `Math.max(3, zoom)` clamp bounds the damage.
4. **Vite/Rolldown + MapLibre v6's ESM module worker.** Verify in a production build, not just
   `npm run dev`. `maplibregl.setWorkerUrl()` is the escape hatch.
5. **WebGL2-only.** ~97% global support, but the `ErrorBoundary` at `Map.jsx:272-292` won't catch
   an async GL context failure. Add an explicit WebGL2 probe → `MapFallback`, or Sentry fills with
   unactionable errors from old devices.
6. **Typography.** CARTO's glyph server has Open Sans / Roboto / Noto; **Work Sans and Kanit 404**.
   Start with Open Sans; self-host Work Sans PBF glyph ranges (~30 KB for `0-255`, one-time build
   step) only if the diff bothers you.
7. **Label collision.** MapLibre hides colliding labels; Leaflet's permanent tooltips didn't.
   Probably an improvement on `/top`, but it *is* a change — `'text-allow-overlap': true` restores
   the old look.
8. **CARTO free-tier terms.** The raster endpoint is already in use and the GL style is the same
   product family, but confirm the vector endpoint's terms for a site with donations/ads.
9. **`crepuscule`'s `@maptiler/sdk` peer-dependency and 2024-03-11 last-push date** (§9). Low
   probability of an actual break given its API surface is standard MapLibre calls, but unverified
   against v6 — resolve this in the first implementation session, not late.
10. **Open-Meteo's `weather-map-layer` is pre-1.0** (v0.0.20) and GPL-2.0 licensed; maintainers
    call it not production-ready themselves. Low risk to the core migration since it's fully
    separable (§14) — confirm the license is acceptable before shipping it regardless.
11. **Lost `showCoverageOnHover` and spiderfy.** Accept, or rebuild the hull via `getClusterLeaves`.
12. **Vector vs raster rendering parity.** Same design family, exact colours pulled — but
    antialiasing, coastline weight and border weight will differ subtly. Hence baseline screenshots.

---

## 14. Weather overlay — Open-Meteo

Package: **`@openmeteo/weather-map-layer`** (npm, v0.0.20 — pre-1.0; the maintainers' own words:
*"still under construction and not yet fully production-ready, API changes may occur"*).
**GPL-2.0 licensed** — check that's acceptable for a client-side runtime dependency before
shipping this (same category of check as CARTO's free-tier ToS in §13). No API key; data is
served from a public CDN.

It registers a custom `om://` protocol that streams Open-Meteo's `.om` files, wrapped as a plain
raster source:

```js
import { omProtocol } from '@openmeteo/weather-map-layer';

maplibregl.addProtocol('om', omProtocol);

const omUrl = 'https://openmeteo-data-spatial.b-cdn.net/dwd_icon/latest.json?variable=precipitation';
// swap `variable=` for cloud_cover / wind / temperature_2m / cape / etc — 120+ variables exist;
// fetch the model's `latest.json` metadata endpoint to see what's available plus `reference_time`

map.addSource('weather', {
    type: 'raster', url: 'om://' + omUrl, tileSize: 512, maxzoom: 12,
});
map.addLayer({ id: 'weather', type: 'raster', source: 'weather',
    paint: { 'raster-opacity': 0.6 } }, 'terminator');
```

For a flight-planning app, `precipitation` is the operationally useful layer (pilots route around
weather); `cloud_cover`/`wind` are secondary. This is **forecast model output** (DWD ICON global),
not live radar — re-fetch `latest.json` periodically (e.g. every 15-30 min) and compare
`reference_time` before swapping the source URL. The metadata "Capture API" adds a documented
0.5-1s delay per request, so don't call it on every render.

Ship as a **user toggle, off by default** — same reasoning as terrain: extra tile requests and
visual noise over the deliberately minimal dark map, and the package's own maturity warning argues
for opt-in over default-on.

**Fully independent of §1-13** — implement last, in its own PR, whenever convenient.

---

## 15. Fallback

If MapLibre hits a blocker you don't want to fight, **the accuracy half of this is independently
shippable on Leaflet**: keep everything as-is, replace `L.curve` with a geodesic polyline built
from the same `greatCircle()` util in §6.1, and fix the `primaryAirport` `ReferenceError`. Hours
of work, near-zero risk, and it fixes the worst-facing bug. You lose the globe, the Mercator
correction, terrain, and the DOM-marker performance win.

---

## 16. Suggested implementation order

Each step is independently promptable — hand the AI one step (plus a pointer to this file) rather
than the whole migration at once. Re-run the relevant part of §12's page matrix after any step
that touches rendering, not just at the very end.

1. **Scaffold.** Add `maplibre-gl`. Build `MapProvider`/`MapGLContext` (§7): a bare globe with the
   CARTO style + sky, `getInitMapPosition()` flipped to `[lng, lat]`. No airports, no route, no
   terminator yet. Confirm the globe renders, atmosphere looks right, and `mapReady` still fires.
2. **Terminator.** Add `crepuscule`, wire up `CrepusculeLive` (§9). Resolve risk #1 immediately —
   confirm it attaches to a raw `maplibregl.Map` — since this step is most likely to need the
   vendoring fallback, and better to know that now than after building on top of it.
3. **Airport layers.** `MapAirportLayers` — source, dots, labels, clustering, click handling (§5).
   Delete `MapMarker*`, `MapTooltipZoom`, `MarkerIcon`, `ClusterIcon`.
4. **Geodesic route.** `utils/geodesic.js`, `MapRoute`, `boundsFromCoordinates` in `MapBound`
   (§6). Delete `MapDrawRoute`. Run the four geometry proofs in §12 (antimeridian, high-latitude
   antimeridian, polar bulge, short-route NaN guard) — these are the core value of the migration.
5. **Remaining ports.** `MapPan`, `MapPing`, `MapSaveView` (§6.3, §11.5).
6. **Polish.** Attribution control + footer line (§10), `map.scss` cleanup, dark
   `.maplibregl-ctrl-attrib` override.
7. **Cleanup + full regression.** Remove the 7 Leaflet packages, add the `vite.config.mjs` chunk
   groups (§11.2), gate the dynamic import on `#map` (§11.1). Run the full §12 page matrix plus
   console-hygiene pass here — this is the "does everything still work" checkpoint before moving
   to optional extras. Run `php artisan test` once, as a pure regression check.
8. **Terrain** (separable). Hillshade layer (§8). Fine as its own PR.
9. **Weather overlay** (separable, fully independent). Open-Meteo layer + user toggle (§14). Fine
   as its own PR, whenever.
