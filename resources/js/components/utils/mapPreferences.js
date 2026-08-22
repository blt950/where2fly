const STORAGE_KEY = 'mapPreferences';

export const DEFAULT_PREFERENCES = {
    terminator: true,
    terrain: false,
    weather: true,
    lists: {},
    projection: 'mercator',
    theme: 'default',
};

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
