import { Fragment, useEffect, useRef, useState } from 'react';
// v6 is ESM-only and ships named exports only — there is no default export to import.
import * as maplibregl from 'maplibre-gl';
// v6 resolves its worker as a sibling of import.meta.url, which after bundling points at a
// /build/assets/maplibre-gl-worker.mjs that Vite never emits. The failed worker load is
// silent — no error event, just a black canvas and zero tile requests — so point MapLibre at
// the worker Vite does bundle for us.
import maplibreWorkerUrl from 'maplibre-gl/dist/maplibre-gl-worker.mjs?worker&url';

import { MapGLContext } from '../context/MapGLContext';
import { applyThemeOverrides, GLYPHS_URL, mapOptions, themeOf } from './mapConfig';

maplibregl.setWorkerUrl(maplibreWorkerUrl);

const MapProvider = ({ center, projection, theme, children }) => {

    const containerRef = useRef(null);
    const [map, setMap] = useState(null);

    // Bumped on every style.load. Children are keyed on it so that swapping the basemap
    // remounts them, and each one re-adds its own sources and layers in tree order — a style
    // swap discards everything the previous style was carrying.
    const [styleEpoch, setStyleEpoch] = useState(0);

    // Read through refs so a theme or projection change never rebuilds the map itself.
    const settings = useRef({ projection, theme });
    settings.current = { projection, theme };

    useEffect(() => {
        const container = containerRef.current;
        let instance = null;

        // .map is display:none below the md breakpoint, so on phones the container never gets
        // a box — building a GL context there would buy a canvas nobody sees plus every tile
        // it requests. Waiting for a non-zero size also avoids initialising into a 0x0 canvas.
        const construct = () => {
            if (instance || !container.offsetWidth || !container.offsetHeight) {
                return;
            }

            // center is read from the first render on purpose: it is the initial camera, not
            // a controlled prop, and later panning must not snap it back.
            instance = new maplibregl.Map(mapOptions(container, center, themeOf(theme).style));
            instance.touchZoomRotate.disableRotation();

            instance.on('style.load', () => {
                const current = settings.current;

                // A style swap resets all of this, so it is applied on every load, not once.
                instance.setProjection({ type: current.projection });
                instance.setSky(themeOf(current.theme).sky);
                instance.setGlyphs(GLYPHS_URL);
                applyThemeOverrides(instance, themeOf(current.theme));

                setMap(instance);
                setStyleEpoch((epoch) => epoch + 1);
            });
        };

        const observer = new ResizeObserver(construct);
        observer.observe(container);
        construct();

        return () => {
            observer.disconnect();
            instance?.remove();
        };
    }, []);

    useEffect(() => {
        map?.setProjection({ type: projection });
    }, [map, projection]);

    // Only swap stylesheets when the theme actually points at a different one — Default and
    // Darker share dark-matter, and re-fetching it would throw away every custom layer for
    // nothing. The ref also skips the stylesheet the map was just constructed with.
    const appliedStyle = useRef(themeOf(theme).style);

    useEffect(() => {
        const next = themeOf(theme).style;

        if (!map || next === appliedStyle.current) {
            return;
        }

        appliedStyle.current = next;
        map.setStyle(next);
    }, [map, theme]);

    // Repaint for the palette. Keyed on styleEpoch as well as theme so it covers both routes
    // in: a same-stylesheet switch, where no style.load fires at all, and the reload after a
    // genuine stylesheet swap.
    useEffect(() => {
        if (!map) {
            return;
        }

        applyThemeOverrides(map, themeOf(theme));
        map.setSky(themeOf(theme).sky);
    }, [map, theme, styleEpoch]);

    return (
        <MapGLContext.Provider value={map}>
            <div className="map" ref={containerRef} />
            {map && <Fragment key={styleEpoch}>{children}</Fragment>}
        </MapGLContext.Provider>
    );
};

export default MapProvider;
