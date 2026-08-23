import { afterEach, describe, expect, it, vi } from 'vitest';

import { readStored, removeStored, writeStored } from '../components/utils/storage';

const memoryStore = () => {
    const data = new Map();

    return {
        getItem: (key) => (data.has(key) ? data.get(key) : null),
        setItem: (key, value) => data.set(key, value),
        removeItem: (key) => data.delete(key),
    };
};

afterEach(() => {
    vi.unstubAllGlobals();
});

describe('readStored/writeStored/removeStored round-trip', () => {
    it('reads back a written value, objects included', () => {
        vi.stubGlobal('localStorage', memoryStore());

        writeStored('key', { a: 1, b: [2, 3] });

        expect(readStored('key')).toEqual({ a: 1, b: [2, 3] });
    });

    it('falls back to null when the key is missing', () => {
        vi.stubGlobal('localStorage', memoryStore());

        expect(readStored('missing')).toBeNull();
    });

    it('falls back to a custom fallback when the key is missing', () => {
        vi.stubGlobal('localStorage', memoryStore());

        expect(readStored('missing', 'default')).toBe('default');
    });

    it('falls back without throwing on corrupted JSON', () => {
        const store = memoryStore();
        store.setItem('key', '{not json');
        vi.stubGlobal('localStorage', store);

        expect(readStored('key', 'fallback')).toBe('fallback');
    });
});

describe('a localStorage that throws on every method (blocked site data)', () => {
    const throwingStore = {
        getItem: () => { throw new Error('blocked'); },
        setItem: () => { throw new Error('blocked'); },
        removeItem: () => { throw new Error('blocked'); },
    };

    it('readStored returns the fallback', () => {
        vi.stubGlobal('localStorage', throwingStore);

        expect(readStored('key', 'fallback')).toBe('fallback');
    });

    it('writeStored does not throw', () => {
        vi.stubGlobal('localStorage', throwingStore);

        expect(() => writeStored('key', 'value')).not.toThrow();
    });

    it('removeStored does not throw', () => {
        vi.stubGlobal('localStorage', throwingStore);

        expect(() => removeStored('key')).not.toThrow();
    });
});
