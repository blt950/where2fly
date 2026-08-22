const STORAGE_KEY = 'mapPreferences';

// Precipitation is on by default because routing around weather is the job this app exists to
// do. Terrain stays off: it is pure decoration at the zooms most sessions sit at, and unlike
// radar it costs DEM tiles the moment you zoom past its gate.
export const DEFAULT_PREFERENCES = {
    terminator: true,
    terrain: false,
    weather: true,
    projection: 'mercator',
};

// Every search reloads the page, so preferences that did not survive a reload would have to be
// set again constantly — the same reason mapPosition is persisted.
export const readPreferences = () => {
    try {
        return { ...DEFAULT_PREFERENCES, ...JSON.parse(localStorage.getItem(STORAGE_KEY) ?? '{}') };
    } catch {
        return { ...DEFAULT_PREFERENCES };
    }
};

export const writePreferences = (preferences) => {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(preferences));
};
