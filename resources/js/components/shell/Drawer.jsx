import { useEffect, useLayoutEffect, useRef, useState } from 'react';

import { stopOffsets } from './drawerStops';
import { useDrawerGestures } from './useDrawerGestures';

const RAISING_FIELDS = 'input:not([type=checkbox]):not([type=radio]):not([type=range]):not([type=hidden]), select, textarea, u-combobox input';
const STOP_LABELS = { peek: 'collapsed', half: 'half open', full: 'fully open' };
const NOTHING = { bottom: 0, left: 0 };

// The panel. `mode` 'panel' (wide screens) is a plain sidebar, the old <main>; 'sheet' is a sheet
// over the map, moved by a transform between resting heights; 'side' (a phone on its side) docks
// left at full height. Knows nothing of its content: it reports what of the map it covers through
// `onCoverChange` ({ bottom, left }) and leaves the rest to the shell.
const Drawer = ({ mode = 'panel', stop, onStopChange, onCoverChange, children }) => {

    const sheet = mode === 'sheet';
    const enabled = mode !== 'panel';
    const mainRef = useRef(null);
    const handleRef = useRef(null);
    const bodyRef = useRef(null);
    const [size, setSize] = useState({ height: 0, width: 0, handle: 0 });
    const offsets = stopOffsets(size.height, size.handle);
    const y = sheet ? offsets[stop] : 0;

    // Stops follow the available height: re-derived on resize and on rotation
    useLayoutEffect(() => {
        if (!enabled) {
            return undefined;
        }

        const measure = () => setSize({
            height: mainRef.current.clientHeight,
            width: mainRef.current.clientWidth,
            handle: handleRef.current?.offsetHeight ?? 0,
        });
        const observer = new ResizeObserver(measure);

        observer.observe(mainRef.current);
        measure();

        return () => observer.disconnect();
    }, [enabled]);

    useEffect(() => {
        onCoverChange(sheet ? { bottom: size.height - y, left: 0 } : mode === 'side' ? { bottom: 0, left: size.width } : NOTHING);
    }, [mode, sheet, size.height, size.width, y, onCoverChange]);

    const handleProps = useDrawerGestures({ sheet, stop, offsets, onStopChange, mainRef, handleRef, bodyRef });

    // The on-screen keyboard would cover a field in a half-open drawer
    const onFocus = (event) => {
        if (sheet && stop !== 'full' && event.target.matches?.(RAISING_FIELDS)) {
            onStopChange('full');
        }
    };

    return (
        <main ref={mainRef} className={enabled ? 'drawer' : undefined} style={sheet ? { '--drawer-y': `${y}px` } : undefined}
            scroll-region="" aria-label={enabled ? 'Panel' : undefined} tabIndex={-1}>
            {sheet && (
                <button ref={handleRef} type="button" className="drawer-handle" aria-label={`Resize panel, ${STOP_LABELS[stop]}`}
                    {...handleProps}>
                    <span className="drawer-grip" aria-hidden="true"></span>
                </button>
            )}
            <div ref={bodyRef} className="drawer-body" scroll-region="" onFocus={onFocus}>
                {children}
            </div>
        </main>
    );
};

export default Drawer;
