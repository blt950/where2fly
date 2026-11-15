// Touch screens have no hover, so tooltips open on focus (a tap) there instead
export const canHover = () => window.matchMedia?.('(hover: hover)').matches ?? true;

export const tooltipOptions = () => ({ container: 'body', trigger: canHover() ? 'hover focus' : 'focus' });

// Inside a clickable row, a tap on a tooltip trigger is for the tooltip, not for the row
export const stopOnTouch = (event) => {
    if (!canHover()) {
        event.stopPropagation();
    }
};
