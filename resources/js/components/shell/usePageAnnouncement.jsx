import { useEffect, useState } from 'react';

// A client-side visit swaps the page silently: announce the new page's title and start reading
// from the panel, as a page load would. Keyed on the title, which each page's <Head> sets (later
// than the visit itself), so a filter or a selection that keeps the page says nothing.
export const usePageAnnouncement = () => {
    const [announcement, setAnnouncement] = useState('');

    useEffect(() => {
        let shown = document.title;
        const observer = new MutationObserver(() => {
            if (document.title !== shown) {
                shown = document.title;
                setAnnouncement(shown);
                document.querySelector('.shell-stage > main')?.focus({ preventScroll: true });
            }
        });
        observer.observe(document.head, { subtree: true, childList: true, characterData: true });

        return () => observer.disconnect();
    }, []);

    return announcement;
};
