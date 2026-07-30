
const TooltipRefresh = () => {

    // React re-renders can remove a tooltip's trigger, orphaning the tip since it
    // never gets the mouseleave to close it — sweep tips with no matching trigger.
    document.querySelectorAll('body > .tooltip').forEach(tip => {
        if (!document.querySelector(`[aria-describedby="${tip.id}"]`)) {
            tip.remove();
        }
    });

    // getOrCreateInstance, never new Tooltip(): re-instantiating an element that
    // already has one leaves two instances fighting, causing stuck-open tooltips.
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(tooltipTriggerEl => {
        bootstrap.Tooltip.getOrCreateInstance(tooltipTriggerEl, {
            container: 'body'
        });
    });

};

export default TooltipRefresh;
