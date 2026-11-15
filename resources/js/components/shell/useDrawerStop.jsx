import { useCallback, useState } from 'react';

const compactNow = () => document.body.classList.contains('shell-compact');

// A page's resting stop on the compact layout: `Page.drawer = 'full'` for pages to read or fill
// in (text pages, account, lists, feedback), where the map has nothing to show; half otherwise.
export const preferredStop = (Page) => Page?.drawer ?? 'half';

// Where the compact layout's drawer rests (drawerStops.js), as the page and the selection ask.
// Both are settled during render, so the drawer reports its new height before the map moves.
export const useDrawerStop = ({ compact, Page, sceneVersion, selectedCard }) => {
    const preferred = preferredStop(Page);
    const [stop, setStop] = useState(compact ? preferred : 'half');

    // A new page, or new content on the same page (results after a search from a raised form,
    // another filter), starts at the page's own stop; so does crossing into the compact layout
    const [settledPage, setSettledPage] = useState({ Page, sceneVersion, compact });
    if (Page !== settledPage.Page || sceneVersion !== settledPage.sceneVersion || compact !== settledPage.compact) {
        setSettledPage({ Page, sceneVersion, compact });
        if (compact) {
            setStop(preferred);
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
    const raiseDrawer = useCallback(() => setStop((current) => (current === 'peek' ? preferred : current)), [preferred]);

    // Tapping the map itself gets the drawer out of the way
    const onBackgroundClick = useCallback(() => {
        if (compactNow()) {
            setStop('peek');
        }
    }, []);

    return { stop, setStop, raiseDrawer, onBackgroundClick };
};
