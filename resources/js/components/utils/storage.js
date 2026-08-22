// localStorage throws outright where a browser blocks site data, and every map call site sits on
// the render path — one unguarded read drops the whole map to the error fallback.
export const readStored = (key, fallback = null) => {
    try {
        const raw = localStorage.getItem(key);

        return raw === null ? fallback : JSON.parse(raw);
    } catch {
        return fallback;
    }
};

export const writeStored = (key, value) => {
    try {
        localStorage.setItem(key, JSON.stringify(value));
    } catch {
        // Blocked or full: the map keeps working, it just forgets between visits.
    }
};

export const removeStored = (key) => {
    try {
        localStorage.removeItem(key);
    } catch {
        // Nothing to do — if we cannot clear it, we cannot have written it either.
    }
};
