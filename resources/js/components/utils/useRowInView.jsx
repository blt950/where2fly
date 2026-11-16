import { useCallback, useEffect, useRef } from 'react';

// Brings the row for `focusKey` into view when the focus changes (it can come from the map),
// unless it already is. Returns the ref callback for a row: ref={rowRef(key)}.
export const useRowInView = (focusKey) => {
    const rows = useRef({});

    useEffect(() => {
        const row = rows.current[focusKey];
        const rect = row?.getBoundingClientRect();

        if (rect && (rect.top < 0 || rect.bottom > window.innerHeight)) {
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }, [focusKey]);

    return useCallback((key) => (row) => { rows.current[key] = row; }, []);
};
