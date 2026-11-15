import { createPortal } from 'react-dom';

import { useMapLayout } from '../context/MapLayoutContext';

// A wide page's main content: over the map area on wide screens, in the drawer on the compact layout
const WidePanel = ({ children }) => {
    const { compact, wideSlot } = useMapLayout();

    if (compact) {
        return children;
    }

    return wideSlot ? createPortal(children, wideSlot) : null;
};

export default WidePanel;
