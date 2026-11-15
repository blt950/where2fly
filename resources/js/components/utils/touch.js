// Touch screens have no hover, so tooltips open on focus (a tap) there instead
export const canHover = () => window.matchMedia?.('(hover: hover)').matches ?? true;

// No fade: a tooltip disposed mid-fade (its content re-rendered) throws from Bootstrap's hide callback
export const tooltipOptions = () => ({ container: 'body', trigger: canHover() ? 'hover focus' : 'focus', animation: false });

// Inside a clickable row, a tap on a tooltip trigger is for the tooltip, not for the row
export const stopOnTouch = (event) => {
    if (!canHover()) {
        event.stopPropagation();
    }
};
