import { tooltipOptions } from './touch';

const TRIGGERS = '[data-bs-toggle="tooltip"]';

// One delegated Bootstrap tooltip for every trigger in the shell, whenever it is rendered: a
// component only marks its element (data-bs-toggle="tooltip", data-bs-title). Returns its undo.
export const installTooltips = () => {
    const tooltip = new bootstrap.Tooltip(document.body, { ...tooltipOptions(), selector: TRIGGERS });
    let shown = null;

    const onShown = (event) => { shown = event.target; };
    const onHidden = (event) => { if (shown === event.target) shown = null; };

    // A tip whose trigger React has removed (a card closed, a visit) would stay up for good
    const observer = new MutationObserver(() => {
        if (shown && !shown.isConnected) {
            bootstrap.Tooltip.getInstance(shown)?.dispose();
            shown = null;
        }
    });

    document.body.addEventListener('shown.bs.tooltip', onShown);
    document.body.addEventListener('hidden.bs.tooltip', onHidden);
    observer.observe(document.body, { childList: true, subtree: true });

    return () => {
        observer.disconnect();
        document.body.removeEventListener('shown.bs.tooltip', onShown);
        document.body.removeEventListener('hidden.bs.tooltip', onHidden);
        tooltip.dispose();
    };
};
