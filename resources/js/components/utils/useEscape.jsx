import { useEffect, useRef } from 'react';

// Whatever else owns Esc: a field, a menu, the map controls, an open dropdown or modal
const OWNERS = 'input, textarea, select, [contenteditable], .map-controls, [data-bs-toggle="dropdown"], .dropdown-menu';
const ownerOpen = () => Boolean(document.querySelector('.dropdown-menu.show, .modal.show'));

// Calls `onEscape` (always the latest one) for an Esc nothing else has a use for
export const useEscape = (onEscape) => {
    const handler = useRef(onEscape);
    handler.current = onEscape;

    useEffect(() => {
        const onKeyDown = (event) => {
            if (event.key !== 'Escape' || event.defaultPrevented || ownerOpen()) return;
            if (event.target instanceof Element && event.target.closest(OWNERS)) return;

            handler.current();
        };

        document.addEventListener('keydown', onKeyDown);
        return () => document.removeEventListener('keydown', onKeyDown);
    }, []);
};
