import { useCallback, useEffect, useState } from 'react';

const compactNow = () => document.body.classList.contains('shell-compact');

// Where the compact layout's drawer rests (drawerStops.js), as the page and the selection ask
export const useDrawerStop = ({ compact, wide, selectedCard }) => {
    const [stop, setStop] = useState('half');

    // Selecting an airport settles the drawer at half: the card shows, and so does the airport on
    // the map (a search box raises the drawer fully, which would leave the map hidden). Done during
    // render, so the drawer reports its new height before the map pans to the airport.
    const [settledFor, setSettledFor] = useState(null);
    if (selectedCard !== settledFor) {
        setSettledFor(selectedCard);
        if (compact && selectedCard) {
            setStop('half');
        }
    }

    const raiseDrawer = useCallback(() => setStop((current) => (current === 'peek' ? 'half' : current)), []);

    // A wide page (feedback) has nothing to show on the map: on the compact layout it opens fully
    useEffect(() => {
        if (wide && compact) {
            setStop('full');
        } else {
            raiseDrawer();
        }
    }, [wide, compact, raiseDrawer]);

    // Tapping the map itself gets the drawer out of the way
    const onBackgroundClick = useCallback(() => {
        if (compactNow()) {
            setStop('peek');
        }
    }, []);

    return { stop, setStop, raiseDrawer, onBackgroundClick };
};
