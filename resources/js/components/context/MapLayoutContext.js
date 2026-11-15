import { createContext, useContext } from 'react';

const NO_INSETS = { top: 0, right: 0, bottom: 0, left: 0 };

// How the page around the map is laid out: `compact` (drawer layouts), and `getInsets()`, what covers
// each edge right now, read when the camera moves. The register* callback refs feed it on wide screens.
export const MapLayoutContext = createContext({
    compact: false,
    getInsets: () => NO_INSETS,
    registerCards: () => {},
    registerFooter: () => {},
});

export const useMapLayout = () => useContext(MapLayoutContext);

// Camera padding: a margin around whatever is covered
export const paddingFor = (insets, margin = 50) => ({
    top: insets.top + margin,
    right: insets.right + margin,
    bottom: insets.bottom + margin,
    left: insets.left + margin,
});

// Offset that centres a point in the uncovered area instead of the whole canvas
export const offsetFor = (insets) => [(insets.left - insets.right) / 2, (insets.top - insets.bottom) / 2];
