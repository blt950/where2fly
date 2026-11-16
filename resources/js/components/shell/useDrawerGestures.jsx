import { useEffect, useRef } from 'react';

import { clampOffset, settle, stepped, tapped } from './drawerStops';

const TAP_SLOP = 4;
// Controls that own a drag of their own; a drag starting on one never moves the drawer
const OWN_DRAG = 'input, textarea, select, .noUi-target, [data-drawer-no-drag]';

// Nothing between the touch and the drawer is scrolled down: a downward drag can't be a scroll up
const scrolledToTop = (target, root) => {
    for (let element = target; element && element !== root; element = element.parentElement) {
        if (element.scrollTop > 0) {
            return false;
        }
    }

    return true;
};

// Everything that moves the sheet between its stops: a drag on the handle (pointer events), a
// drag or a wheel on the content, a tap or a key on the handle. Returns the handle's props.
export const useDrawerGestures = ({ sheet, stop, offsets, onStopChange, mainRef, handleRef, bodyRef }) => {
    const y = sheet ? offsets[stop] : 0;

    const place = (offset) => mainRef.current.style.setProperty('--drawer-y', `${offset}px`);

    // One drag, from the handle (pointer events) or from the content (touch events, A6). Read
    // through a ref, so the content's listeners, added once, always see this render's stops.
    const drag = useRef(null);
    const gestures = useRef(null);
    const onStopChangeRef = useRef(onStopChange);
    onStopChangeRef.current = onStopChange;
    gestures.current = {
        stop,
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

    // Dragging the content moves the drawer too, as sheets do: up while it isn't fully open (the
    // content scrolls once it is), down once nothing under the finger can scroll up any further.
    // Touch events, as only those can stop the scroll natively.
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

            // Decided once, on the first clear movement: mostly vertical, and up from below full or
            // down from the top
            if (touch.ours === null) {
                const dx = point.clientX - touch.x;
                const dy = point.clientY - touch.y;

                if (Math.abs(dx) <= TAP_SLOP && Math.abs(dy) <= TAP_SLOP) return;

                const vertical = Math.abs(dy) > Math.abs(dx);
                touch.ours = vertical && (dy < 0 ? gestures.current.stop !== 'full' : touch.top);
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

        // A wheel or trackpad does the same a stop at a time; one gesture's trailing events are let go
        let wheelUntil = 0;
        const onWheel = (event) => {
            const { stop: current } = gestures.current;
            const step = event.deltaY > 0 && current !== 'full' ? (current === 'peek' ? 'half' : 'full')
                : event.deltaY < 0 && current === 'full' && scrolledToTop(event.target, mainRef.current) ? 'half'
                    : null;

            if (event.timeStamp < wheelUntil || !step || event.target.closest?.(OWN_DRAG)) {
                if (event.timeStamp < wheelUntil && step) event.preventDefault();
                return;
            }

            event.preventDefault();
            wheelUntil = event.timeStamp + 500;
            onStopChangeRef.current(step);
        };

        body.addEventListener('wheel', onWheel, { passive: false });
        body.addEventListener('touchstart', onStart, { passive: true });
        body.addEventListener('touchmove', onMove, { passive: false });
        body.addEventListener('touchend', onEnd);
        body.addEventListener('touchcancel', onEnd);

        return () => {
            body.removeEventListener('wheel', onWheel);
            body.removeEventListener('touchstart', onStart);
            body.removeEventListener('touchmove', onMove);
            body.removeEventListener('touchend', onEnd);
            body.removeEventListener('touchcancel', onEnd);
        };
    }, [sheet, bodyRef, mainRef]);

    // Pointer taps are handled above; a click without a pointer (detail 0) is the keyboard
    const onClick = (event) => {
        if (event.detail === 0) {
            onStopChange(stepped(stop));
        }
    };

    return { onPointerDown, onPointerMove, onPointerUp, onPointerCancel, onClick };
};
