import { useCallback, useRef, useState } from 'react';

// A page's resting stop on the compact layout: `Page.drawer = 'full'` for pages to read or fill
// in (text pages, account, lists, feedback); 'keep' for results, which stay as the search left them
// (a collapsed drawer comes back to half); half otherwise.
export const preferredStop = (Page) => Page?.drawer ?? 'half';
const settleTo = (preferred, current) => (preferred !== 'keep' ? preferred : current === 'peek' ? 'half' : current);

// Where the compact layout's drawer rests (drawerStops.js), as the page and the selection ask.
// Both are settled during render, so the drawer reports its new height before the map moves.
// `mode` from useLayoutMode: only a sheet ('sheet') has stops to move between; the side-docked
// drawer keeps its stop for when the phone turns upright again.
export const useDrawerStop = ({ mode, Page, sceneVersion, selectedCard }) => {
    const compact = mode !== 'panel';
    const preferred = preferredStop(Page);
    const currentMode = useRef(mode);
    currentMode.current = mode;
    const [stop, setStop] = useState(compact ? settleTo(preferred, 'half') : 'half');

    // A new page, or new content on the same page (results after a search from a raised form,
    // another filter), starts at the page's own stop; so does a new layout (turning the phone)
    const [settledPage, setSettledPage] = useState({ Page, sceneVersion, mode });
    if (Page !== settledPage.Page || sceneVersion !== settledPage.sceneVersion || mode !== settledPage.mode) {
        setSettledPage({ Page, sceneVersion, mode });
        if (compact) {
            setStop((current) => settleTo(preferred, current));
        }
    }

    // Selecting an airport settles the drawer at half: the card shows, and so does the airport on
    // the map (a search box raises the drawer fully, which would leave the map hidden)
    const [settledFor, setSettledFor] = useState(null);
    if (selectedCard !== settledFor) {
        setSettledFor(selectedCard);
        if (compact && selectedCard) {
            setStop('half');
        }
    }

    // A collapsed drawer comes back: the bottom nav, or a tap on the airport already selected
    const raiseDrawer = useCallback(() => setStop((current) => (current === 'peek' ? settleTo(preferred, current) : current)), [preferred]);

    // Tapping the map itself gets a sheet out of the way
    const onBackgroundClick = useCallback(() => {
        if (currentMode.current === 'sheet') {
            setStop('peek');
        }
    }, []);

    return { stop, setStop, raiseDrawer, onBackgroundClick };
};
