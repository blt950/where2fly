import { Fragment, useEffect, useRef, useState } from 'react';
import * as maplibregl from 'maplibre-gl';
import maplibreWorkerUrl from 'maplibre-gl/dist/maplibre-gl-worker.mjs?worker&url';
import { MapGLContext } from '../context/MapGLContext';
import { applyTheme, GLYPHS_URL, mapOptions, themeOf } from './mapConfig';

maplibregl.setWorkerUrl(maplibreWorkerUrl);

const MapProvider = ({ view, projection, theme, children }) => {

    const containerRef = useRef(null);
    const [map, setMap] = useState(null);
    const [styleEpoch, setStyleEpoch] = useState(0);
    const settings = useRef({ projection, theme });
    settings.current = { projection, theme };

    useEffect(() => {
        const container = containerRef.current;
        let instance = null;

        const construct = () => {
            if (instance || !container.offsetWidth || !container.offsetHeight) {
                return;
            }

            instance = new maplibregl.Map(mapOptions(container, view, themeOf(theme).style));
            instance.touchZoomRotate.disableRotation();

            instance.on('style.load', () => {
                const current = settings.current;

                instance.setProjection({ type: current.projection });
                instance.setGlyphs(GLYPHS_URL);
                applyTheme(instance, themeOf(current.theme));

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

    const appliedStyle = useRef(themeOf(theme).style);
    useEffect(() => {
        const next = themeOf(theme).style;

        if (!map || next === appliedStyle.current) {
            return;
        }

        appliedStyle.current = next;
        map.setStyle(next);
    }, [map, theme]);

    useEffect(() => {
        if (!map) {
            return;
        }

        applyTheme(map, themeOf(theme));
    }, [map, theme, styleEpoch]);

    return (
        <MapGLContext.Provider value={map}>
            <div className="map" ref={containerRef} />
            {map && <Fragment key={styleEpoch}>{children}</Fragment>}
        </MapGLContext.Provider>
    );
};

export default MapProvider;
