// Where the drawer rests, as its top edge's offset from the top of the area it moves in.
// Derived from the available height, so a resize or rotation re-derives every stop.
export const STOPS = ['full', 'half', 'peek'];

// Above this (px/ms) a release counts as a flick and moves one stop in its direction
export const FLICK_VELOCITY = 0.5;

export const stopOffsets = (height, handleHeight) => ({
    full: 0,
    half: Math.round(height * 0.5),
    peek: Math.max(0, height - handleHeight),
});

export const clampOffset = (offsets, y) => Math.min(offsets.peek, Math.max(offsets.full, y));

// A slow drag settles on the nearest stop; a flick goes to the next stop in its direction
// (positive velocity = downward), whichever stop it started from.
export const settle = (offsets, y, velocity) => {
    if (Math.abs(velocity) > FLICK_VELOCITY) {
        const ahead = STOPS.filter((stop) => (velocity > 0 ? offsets[stop] > y : offsets[stop] < y));

        if (!ahead.length) {
            return velocity > 0 ? 'peek' : 'full';
        }

        // STOPS runs top to bottom: downward takes the first stop below, upward the last above
        return velocity > 0 ? ahead[0] : ahead[ahead.length - 1];
    }

    return STOPS.reduce((best, stop) => (Math.abs(offsets[stop] - y) < Math.abs(offsets[best] - y) ? stop : best));
};

// A tap on the handle opens the drawer further, and from full drops back to half
export const tapped = (stop) => ({ peek: 'half', half: 'full', full: 'half' }[stop]);

// The keyboard path steps through every height, wrapping round
export const stepped = (stop) => ({ peek: 'half', half: 'full', full: 'peek' }[stop]);
