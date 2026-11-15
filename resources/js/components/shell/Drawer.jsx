import { useEffect, useLayoutEffect, useRef, useState } from 'react';

import { clampOffset, settle, stepped, stopOffsets, tapped } from './drawerStops';

const TAP_SLOP = 4;
const RAISING_FIELDS = 'input:not([type=checkbox]):not([type=radio]):not([type=range]):not([type=hidden]), select, textarea, u-combobox input';
const STOP_LABELS = { peek: 'collapsed', half: 'half open', full: 'fully open' };

// The panel. On wide screens a plain sidebar (the old <main>); in the compact layout a sheet over
// the map, moved by a transform between resting heights. Knows nothing of its content: it reports
// how much of the map it covers through `onVisibleChange` and leaves the rest to the shell.
const Drawer = ({ enabled, stop, onStopChange, onVisibleChange, children }) => {

    const mainRef = useRef(null);
    const handleRef = useRef(null);
    const drag = useRef(null);
    const [size, setSize] = useState({ height: 0, handle: 0 });
    const offsets = stopOffsets(size.height, size.handle);
    const y = enabled ? offsets[stop] : 0;

    // Stops follow the available height: re-derived on resize and on rotation
    useLayoutEffect(() => {
        if (!enabled) {
            return undefined;
        }

        const measure = () => setSize({ height: mainRef.current.clientHeight, handle: handleRef.current?.offsetHeight ?? 0 });
        const observer = new ResizeObserver(measure);

        observer.observe(mainRef.current);
        measure();

        return () => observer.disconnect();
    }, [enabled]);

    useEffect(() => {
        onVisibleChange(enabled ? size.height - y : 0);
    }, [enabled, size.height, y, onVisibleChange]);

    const place = (offset) => mainRef.current.style.setProperty('--drawer-y', `${offset}px`);

    const onPointerDown = (event) => {
        if (event.button !== 0) return;

        drag.current = { startY: event.clientY, base: y, y, at: event.timeStamp, velocity: 0, moved: false };
        handleRef.current.setPointerCapture(event.pointerId);
    };

    const onPointerMove = (event) => {
        const current = drag.current;

        if (!current) return;

        const travel = event.clientY - current.startY;

        if (!current.moved && Math.abs(travel) <= TAP_SLOP) return;

        if (!current.moved) {
            current.moved = true;
            mainRef.current.classList.add('dragging');
        }

        const next = clampOffset(offsets, current.base + travel);
        current.velocity = (next - current.y) / Math.max(1, event.timeStamp - current.at);
        current.y = next;
        current.at = event.timeStamp;
        place(next);
    };

    const onPointerEnd = () => {
        const current = drag.current;

        if (!current) return;

        drag.current = null;
        mainRef.current.classList.remove('dragging');

        const next = current.moved ? settle(offsets, current.y, current.velocity) : tapped(stop);

        // Snap back explicitly: settling on the same stop wouldn't re-render
        place(offsets[next]);
        onStopChange(next);
    };

    // Pointer taps are handled above; a click without a pointer (detail 0) is the keyboard
    const onClick = (event) => {
        if (event.detail === 0) {
            onStopChange(stepped(stop));
        }
    };

    // The on-screen keyboard would cover a field in a half-open drawer
    const onFocus = (event) => {
        if (enabled && stop !== 'full' && event.target.matches?.(RAISING_FIELDS)) {
            onStopChange('full');
        }
    };

    return (
        <main ref={mainRef} className={enabled ? 'drawer' : undefined} style={enabled ? { '--drawer-y': `${y}px` } : undefined}
            scroll-region="" aria-label={enabled ? 'Panel' : undefined}>
            {enabled && (
                <button ref={handleRef} type="button" className="drawer-handle" aria-label={`Resize panel, ${STOP_LABELS[stop]}`}
                    onPointerDown={onPointerDown} onPointerMove={onPointerMove} onPointerUp={onPointerEnd}
                    onPointerCancel={onPointerEnd} onClick={onClick}>
                    <span className="drawer-grip" aria-hidden="true"></span>
                </button>
            )}
            <div className="drawer-body" scroll-region="" onFocus={onFocus}>
                {children}
            </div>
        </main>
    );
};

export default Drawer;
