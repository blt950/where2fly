import { Fragment, useEffect, useRef, useState } from 'react';
import * as maplibregl from 'maplibre-gl';
import maplibreWorkerUrl from 'maplibre-gl/dist/maplibre-gl-worker.mjs?worker&url';
import { MapGLContext } from '../context/MapGLContext';
import { mapOptions, themedStyle, themeOf } from './mapConfig';

maplibregl.setWorkerUrl(maplibreWorkerUrl);

const MapProvider = ({ view, projection, theme, children }) => {

    const containerRef = useRef(null);
    const [map, setMap] = useState(null);
    const [styleEpoch, setStyleEpoch] = useState(0);
    const [constructError, setConstructError] = useState(null);
    const settings = useRef({ projection, theme });

    // transformStyle runs on every style load, long after mount, and has to read whatever is
    // current by then — hence the ref, written after each render rather than during it.
    useEffect(() => {
        settings.current = { projection, theme };
    });

    // Mount only: view and theme seed the first map, and the effects below own every later
    // change. Re-running this would rebuild the map underneath its children.
    useEffect(() => {
        const container = containerRef.current;
        let instance = null;

        const construct = () => {
            if (instance || !container.offsetWidth || !container.offsetHeight) {
                return;
            }

            try {
                instance = new maplibregl.Map({
                    ...mapOptions(container, view, themeOf(theme).style),
                    // setStyle reuses this transform, so a theme swap arrives already themed too.
                    transformStyle: (_previous, next) => themedStyle(
                        next, themeOf(settings.current.theme), settings.current.projection,
                    ),
                });
            } catch (error) {
                // Called from the ResizeObserver this throw would bypass the error boundary, so
                // it is rethrown from render instead.
                observer.disconnect();
                setConstructError(error);

                return;
            }

            instance.touchZoomRotate.disableRotation();

            instance.on('style.load', () => {
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
        // eslint-disable-next-line react-hooks/exhaustive-deps
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

    if (constructError) {
        throw constructError;
    }

    return (
        <MapGLContext.Provider value={map}>
            <div className="map" ref={containerRef} />
            {map && <Fragment key={styleEpoch}>{children}</Fragment>}
        </MapGLContext.Provider>
    );
};

export default MapProvider;
