const STORAGE_KEY = 'mapPreferences';

// Terrain and weather are off by default: both cost tile requests, and both add visual noise
// over a basemap chosen for being minimal.
export const DEFAULT_PREFERENCES = {
    terminator: true,
    terrain: false,
    weather: false,
    projection: 'globe',
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
