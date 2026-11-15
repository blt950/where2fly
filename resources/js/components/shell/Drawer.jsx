import { useEffect, useLayoutEffect, useRef, useState } from 'react';

import { clampOffset, settle, stepped, stopOffsets, tapped } from './drawerStops';

const TAP_SLOP = 4;
const RAISING_FIELDS = 'input:not([type=checkbox]):not([type=radio]):not([type=range]):not([type=hidden]), select, textarea, u-combobox input';
// Controls that own a drag of their own; a drag starting on one never moves the drawer
const OWN_DRAG = 'input, textarea, select, .noUi-target, [data-drawer-no-drag]';
const STOP_LABELS = { peek: 'collapsed', half: 'half open', full: 'fully open' };
const NOTHING = { bottom: 0, left: 0 };

// Nothing between the touch and the drawer is scrolled down: a downward drag can't be a scroll up
const scrolledToTop = (target, root) => {
    for (let element = target; element && element !== root; element = element.parentElement) {
        if (element.scrollTop > 0) {
            return false;
        }
    }

    return true;
};

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
    const drag = useRef(null);
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

    const place = (offset) => mainRef.current.style.setProperty('--drawer-y', `${offset}px`);

    // One drag, from the handle (pointer events) or from the content (touch events, A6). Read
    // through a ref, so the content's listeners, added once, always see this render's stops.
    const gestures = useRef(null);
    gestures.current = {
        begin: (clientY, at) => {
            drag.current = { startY: clientY, base: y, y, at, velocity: 0, moved: false };
        },
        follow: (clientY, at) => {
            const current = drag.current;
            const travel = clientY - current.startY;

            if (!current.moved && Math.abs(travel) <= TAP_SLOP) return;

            if (!current.moved) {
                current.moved = true;
                mainRef.current.classList.add('dragging');
            }

            const next = clampOffset(offsets, current.base + travel);
            current.velocity = (next - current.y) / Math.max(1, at - current.at);
            current.y = next;
            current.at = at;
            place(next);
        },
        finish: () => {
            const current = drag.current;
            drag.current = null;
            mainRef.current.classList.remove('dragging');

            const next = current.moved ? settle(offsets, current.y, current.velocity) : tapped(stop);

            // Snap back explicitly: settling on the same stop wouldn't re-render
            place(offsets[next]);
            onStopChange(next);
        },
        // A system gesture or an incoming call took the pointer: put the drawer back, no tap, no move
        abandon: () => {
            drag.current = null;
            mainRef.current.classList.remove('dragging');
            place(offsets[stop]);
        },
    };

    const onPointerDown = (event) => {
        if (event.button !== 0) return;

        gestures.current.begin(event.clientY, event.timeStamp);
        handleRef.current.setPointerCapture(event.pointerId);
    };

    const onPointerMove = (event) => {
        if (drag.current) gestures.current.follow(event.clientY, event.timeStamp);
    };

    const onPointerUp = () => {
        if (drag.current) gestures.current.finish();
    };

    const onPointerCancel = () => {
        if (drag.current) gestures.current.abandon();
    };

    // A6: dragging the content down moves the drawer too, as sheets do, once nothing under the
    // finger can scroll up any further. Touch events, as only those can stop the scroll natively.
    useEffect(() => {
        if (!sheet) {
            return undefined;
        }

        const body = bodyRef.current;
        let touch = null;

        const onStart = (event) => {
            const point = event.touches[0];
            touch = event.touches.length === 1 && !event.target.closest?.(OWN_DRAG)
                ? { x: point.clientX, y: point.clientY, top: scrolledToTop(event.target, mainRef.current), ours: null }
                : null;
        };

        const onMove = (event) => {
            if (!touch) return;

            const point = event.touches[0];

            // Decided once, on the first clear movement: down and mostly vertical, from the top
            if (touch.ours === null) {
                const dx = point.clientX - touch.x;
                const dy = point.clientY - touch.y;

                if (Math.abs(dx) <= TAP_SLOP && Math.abs(dy) <= TAP_SLOP) return;

                touch.ours = touch.top && dy > 0 && Math.abs(dy) > Math.abs(dx);
                if (touch.ours) gestures.current.begin(touch.y, event.timeStamp);
            }

            if (touch.ours) {
                event.preventDefault();
                gestures.current.follow(point.clientY, event.timeStamp);
            }
        };

        const onEnd = (event) => {
            if (touch?.ours && drag.current) {
                gestures.current[event.type === 'touchcancel' ? 'abandon' : 'finish']();
            }
            touch = null;
        };

        body.addEventListener('touchstart', onStart, { passive: true });
        body.addEventListener('touchmove', onMove, { passive: false });
        body.addEventListener('touchend', onEnd);
        body.addEventListener('touchcancel', onEnd);

        return () => {
            body.removeEventListener('touchstart', onStart);
            body.removeEventListener('touchmove', onMove);
            body.removeEventListener('touchend', onEnd);
            body.removeEventListener('touchcancel', onEnd);
        };
    }, [sheet]);

    // Pointer taps are handled above; a click without a pointer (detail 0) is the keyboard
    const onClick = (event) => {
        if (event.detail === 0) {
            onStopChange(stepped(stop));
        }
    };

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
                    onPointerDown={onPointerDown} onPointerMove={onPointerMove} onPointerUp={onPointerUp}
                    onPointerCancel={onPointerCancel} onClick={onClick}>
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
