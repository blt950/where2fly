import { useEffect, useRef, useState } from 'react';
// v6 is ESM-only and ships named exports only — there is no default export to import.
import * as maplibregl from 'maplibre-gl';
// v6 resolves its worker as a sibling of import.meta.url, which after bundling points at a
// /build/assets/maplibre-gl-worker.mjs that Vite never emits. The failed worker load is
// silent — no error event, just a black canvas and zero tile requests — so point MapLibre at
// the worker Vite does bundle for us.
import maplibreWorkerUrl from 'maplibre-gl/dist/maplibre-gl-worker.mjs?worker&url';

import { MapGLContext } from '../context/MapGLContext';
import { GLYPHS_URL, mapOptions, SKY } from './mapConfig';

maplibregl.setWorkerUrl(maplibreWorkerUrl);

const MapProvider = ({ center, children }) => {

    const containerRef = useRef(null);
    const [map, setMap] = useState(null);

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
            instance = new maplibregl.Map(mapOptions(container, center));
            instance.touchZoomRotate.disableRotation();

            instance.on('style.load', () => {
                instance.setProjection({ type: 'globe' });
                instance.setSky(SKY);
                instance.setGlyphs(GLYPHS_URL);
                setMap(instance);
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

    return (
        <MapGLContext.Provider value={map}>
            <div className="map" ref={containerRef} />
            {map && children}
        </MapGLContext.Provider>
    );
};

export default MapProvider;
