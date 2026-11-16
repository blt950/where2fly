import { useCallback, useRef } from 'react';

// What covers the map right now. On the compact layouts, what the drawer reports covering (its
// visible height as a sheet, its width docked to the side) and the header over the map. On wide
// screens, the card stack and the footer. Elements are handed over (register*) to be measured.
// Read at the moment the camera moves (paddingFor / offsetFor), never cached.
export const useMapInsets = (mode) => {
    const currentMode = useRef(mode);
    currentMode.current = mode;
    const cover = useRef({ bottom: 0, left: 0 });
    const mapRef = useRef(null);
    const cards = useRef(null);
    const footer = useRef(null);
    const header = useRef(null);

    const onCoverChange = useCallback((covered) => { cover.current = covered; }, []);
    const registerCards = useCallback((element) => { cards.current = element; }, []);
    const registerFooter = useCallback((element) => { footer.current = element; }, []);
    const registerHeader = useCallback((element) => { header.current = element; }, []);

    const getInsets = useCallback(() => {
        // The sheet's header lies over the map (shell.scss); the side layout has none
        if (currentMode.current !== 'panel') {
            return { top: currentMode.current === 'sheet' ? header.current?.offsetHeight ?? 0 : 0, right: 0, ...cover.current };
        }

        const mapLeft = mapRef.current?.getBoundingClientRect().left ?? 0;
        const cardsRight = cards.current?.getBoundingClientRect().right;

        return {
            top: 0,
            right: 0,
            bottom: footer.current?.offsetHeight ?? 0,
            left: cardsRight === undefined ? 0 : Math.max(0, cardsRight - mapLeft),
        };
    }, []);

    return { getInsets, onCoverChange, registerCards, registerFooter, registerHeader, mapRef };
};
