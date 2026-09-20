// The lists overlay is fed from localStorage and from the API, so neither source is
// guaranteed to hold the array shape it maps over — a nullish check alone let a stale
// or malformed cache entry take the whole map down through the error boundary.
export const asList = (value) => (Array.isArray(value) ? value : []);

// One source for every visible list — the airports already carry their list's color, so
// merging keeps a single set of layers instead of one per list.
export const mergeListAirports = (lists) => Object.assign(
    {},
    ...asList(lists)
        .filter((list) => list && !list.hidden)
        .map((list) => list.airports)
        .filter((airports) => airports && typeof airports === 'object'),
);
