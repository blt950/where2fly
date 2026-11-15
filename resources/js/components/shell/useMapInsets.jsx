import { useCallback, useRef } from 'react';

const FOOTER_HEIGHT = 30;

// What the card stack beside the panel covers of the map, measured when the camera asks
const cardStackWidth = () => {
    const stack = document.querySelector('.popup-container');
    const map = document.querySelector('#map .maplibregl-map');

    if (!stack || !map || !stack.querySelector('.popup-card')) {
        return 0;
    }

    return Math.max(0, stack.getBoundingClientRect().right - map.getBoundingClientRect().left);
};

// What covers the map right now: the drawer on the compact layout, the card stack and footer
// otherwise. Read at the moment the camera moves (paddingFor / offsetFor), never cached.
export const useMapInsets = () => {
    const drawerVisible = useRef(0);
    const onDrawerVisible = useCallback((px) => { drawerVisible.current = px; }, []);

    const getInsets = useCallback(() => (document.body.classList.contains('shell-compact')
        ? { top: 0, right: 0, bottom: drawerVisible.current, left: 0 }
        : { top: 0, right: 0, bottom: FOOTER_HEIGHT, left: cardStackWidth() }), []);

    return { getInsets, onDrawerVisible };
};
