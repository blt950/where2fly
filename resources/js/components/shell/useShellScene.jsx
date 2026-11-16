import { useMemo, useRef } from 'react';

import { SCENE_DEFAULTS } from '../map/mapScene';

// Pages declare their map as a static `mapScene(props)`, read during render so the first visit
// constructs the map with the right camera instead of correcting it afterwards. A page with
// `keepsMap` (the text pages) leaves the map exactly as the previous page had it.
export const useShellScene = (Page, props) => {
    const lastScene = useRef(SCENE_DEFAULTS);
    const scene = useMemo(
        () => (Page?.keepsMap ? lastScene.current : Page?.mapScene?.(props) ?? SCENE_DEFAULTS),
        [Page, props],
    );
    lastScene.current = scene;

    return scene;
};
