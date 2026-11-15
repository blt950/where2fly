import { useEffect, useState } from 'react';

// Below Bootstrap's lg: the drawer layout. Tablets get it too, as the card stack would cover most
// of a narrow map.
export const COMPACT_QUERY = '(max-width: 991.98px)';

const matches = () => typeof window !== 'undefined' && window.matchMedia?.(COMPACT_QUERY).matches === true;

export const useCompactLayout = () => {
    const [compact, setCompact] = useState(matches);

    useEffect(() => {
        const query = window.matchMedia?.(COMPACT_QUERY);
        const update = () => setCompact(query.matches);

        query?.addEventListener('change', update);

        return () => query?.removeEventListener('change', update);
    }, []);

    return compact;
};
