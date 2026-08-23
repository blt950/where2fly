import { afterEach, describe, expect, it, vi } from 'vitest';

import { DEFAULT_PREFERENCES, readPreferences } from '../components/utils/mapPreferences';

const memoryStore = (initial = {}) => {
    const data = new Map(Object.entries(initial));

    return {
        getItem: (key) => (data.has(key) ? data.get(key) : null),
        setItem: (key, value) => data.set(key, value),
        removeItem: (key) => data.delete(key),
    };
};

afterEach(() => {
    vi.unstubAllGlobals();
});

describe('readPreferences', () => {
    it('returns exactly the defaults when storage is empty', () => {
        vi.stubGlobal('localStorage', memoryStore());

        expect(readPreferences()).toEqual(DEFAULT_PREFERENCES);
    });

    it('merges a stored partial under the defaults, stored value winning', () => {
        vi.stubGlobal('localStorage', memoryStore({ mapPreferences: JSON.stringify({ theme: 'light' }) }));

        expect(readPreferences()).toEqual({ ...DEFAULT_PREFERENCES, theme: 'light' });
    });
});
