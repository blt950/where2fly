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

// What covers the map right now: what the drawer reports covering on the compact layouts (its
// visible height as a sheet, its width docked to the side), the card stack and footer on wide
// screens. Read at the moment the camera moves (paddingFor / offsetFor), never cached.
export const useMapInsets = (mode) => {
    const currentMode = useRef(mode);
    currentMode.current = mode;
    const cover = useRef({ bottom: 0, left: 0 });
    const onCoverChange = useCallback((covered) => { cover.current = covered; }, []);

    const getInsets = useCallback(() => (currentMode.current === 'panel'
        ? { top: 0, right: 0, bottom: FOOTER_HEIGHT, left: cardStackWidth() }
        : { top: 0, right: 0, ...cover.current }), []);

    return { getInsets, onCoverChange };
};
