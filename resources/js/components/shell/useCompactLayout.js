import { useEffect, useState } from 'react';

// Below Bootstrap's lg: the drawer layout. Tablets get it too, as the card stack would cover most
// of a narrow map.
export const COMPACT_QUERY = '(max-width: 991.98px)';

// A phone on its side: too short for a sheet over the map (header, handle, tabs and nav took 250
// of 390px), so the drawer docks to the left at full height instead
export const SIDE_QUERY = '(max-width: 991.98px) and (orientation: landscape) and (max-height: 500px)';

const useMedia = (query) => {
    const [matches, setMatches] = useState(() => typeof window !== 'undefined' && window.matchMedia?.(query).matches === true);

    useEffect(() => {
        const media = window.matchMedia?.(query);
        const update = () => setMatches(media.matches);

        media?.addEventListener('change', update);

        return () => media?.removeEventListener('change', update);
    }, [query]);

    return matches;
};

export const useCompactLayout = () => useMedia(COMPACT_QUERY);

// 'panel' on wide screens (the sidebar), 'sheet' (the drawer over the map), 'side' (docked left)
export const useLayoutMode = () => {
    const compact = useMedia(COMPACT_QUERY);
    const side = useMedia(SIDE_QUERY);

    if (!compact) {
        return 'panel';
    }

    return side ? 'side' : 'sheet';
};
