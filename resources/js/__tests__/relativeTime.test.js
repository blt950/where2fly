import { afterEach, describe, expect, it, vi } from 'vitest';

import fromNow from '../components/utils/RelativeTime';

const NOW = new Date('2026-08-23T12:00:00Z');

afterEach(() => {
    vi.useRealTimers();
});

describe('fromNow', () => {
    it('formats 2 days in the past', () => {
        vi.useFakeTimers();
        vi.setSystemTime(NOW);

        expect(fromNow(new Date(NOW.getTime() - 2 * 86400000))).toBe('2 days ago');
    });

    it('formats 1 hour in the past', () => {
        vi.useFakeTimers();
        vi.setSystemTime(NOW);

        expect(fromNow(new Date(NOW.getTime() - 3600000))).toBe('1 hour ago');
    });

    it('rounds ~90 seconds in the future to 2 minutes', () => {
        vi.useFakeTimers();
        vi.setSystemTime(NOW);

        expect(fromNow(new Date(NOW.getTime() + 90000))).toBe('in 2 minutes');
    });

    it('formats exactly 1 day in the past as "yesterday" (numeric: auto)', () => {
        vi.useFakeTimers();
        vi.setSystemTime(NOW);

        expect(fromNow(new Date(NOW.getTime() - 86400000))).toBe('yesterday');
    });
});
