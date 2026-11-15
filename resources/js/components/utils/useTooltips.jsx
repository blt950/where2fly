import { useEffect } from 'react';

import { tooltipOptions } from './touch';

// Bootstrap tooltips for the triggers inside `ref`, disposed whenever the content changes or
// the page goes. Without the dispose, a tip open during a client-side visit is left orphaned.
export const useTooltips = (ref, deps) => {
    useEffect(() => {
        const instances = [...(ref.current?.querySelectorAll('[data-bs-toggle="tooltip"]') ?? [])]
            .map((trigger) => bootstrap.Tooltip.getOrCreateInstance(trigger, tooltipOptions()));

        return () => instances.forEach((instance) => instance.dispose());
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, deps);
};
