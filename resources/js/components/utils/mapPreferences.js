import { readStored, writeStored } from './storage';

const STORAGE_KEY = 'mapPreferences';

export const DEFAULT_PREFERENCES = {
    terminator: true,
    terrain: true,
    weather: true,
    lists: {},
    projection: 'mercator',
    theme: 'default',
};

export const readPreferences = () => ({ ...DEFAULT_PREFERENCES, ...readStored(STORAGE_KEY) });

export const writePreferences = (preferences) => writeStored(STORAGE_KEY, preferences);
