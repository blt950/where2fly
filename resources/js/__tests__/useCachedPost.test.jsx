// @vitest-environment jsdom
import { cleanup, render, screen, waitFor } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { cachedPost, clearRequestCache, useCachedPost } from '../components/utils/useCachedPost';

vi.mock('@sentry/react', () => ({ captureException: vi.fn() }));

const answer = (data) => Promise.resolve({ ok: true, status: 200, json: () => Promise.resolve({ data }) });

const Probe = ({ body }) => {
    const data = useCachedPost('/api/x', body);
    return <span data-testid="out">{data ? data.name : 'loading'}</span>;
};

beforeEach(() => {
    clearRequestCache();
    document.head.innerHTML = '<meta name="csrf-token" content="fresh-token">';
    vi.stubGlobal('fetch', vi.fn(() => answer({ name: 'ENGM' })));
});

afterEach(() => {
    cleanup();
    vi.useRealTimers();
    vi.unstubAllGlobals();
});

describe('useCachedPost', () => {
    it('sends the current CSRF token from the meta tag', async () => {
        await cachedPost('/api/x', { a: 1 });

        expect(fetch).toHaveBeenCalledWith('/api/x', expect.objectContaining({
            headers: expect.objectContaining({ 'X-CSRF-TOKEN': 'fresh-token' }),
        }));
    });

    it('shows an answer it already has on the first render, without asking again', async () => {
        await cachedPost('/api/x', { id: 1 });

        render(<Probe body={{ id: 1 }} />);

        expect(screen.getByTestId('out').textContent).toBe('ENGM');
        await waitFor(() => expect(fetch).toHaveBeenCalledTimes(1));
    });

    it('never shows one request\'s answer under another', async () => {
        const pending = [];
        fetch.mockImplementation(() => new Promise((resolve) => pending.push(resolve)));

        const { rerender } = render(<Probe body={{ id: 1 }} />);
        pending[0]({ ok: true, status: 200, json: () => Promise.resolve({ data: { name: 'ENGM' } }) });
        await waitFor(() => expect(screen.getByTestId('out').textContent).toBe('ENGM'));

        rerender(<Probe body={{ id: 2 }} />);
        expect(screen.getByTestId('out').textContent).toBe('loading');

        rerender(<Probe body={null} />);
        expect(screen.getByTestId('out').textContent).toBe('loading');
    });

    it('asks again once an answer is older than five minutes', async () => {
        vi.useFakeTimers({ toFake: ['Date'] });
        await cachedPost('/api/x', { id: 1 });

        vi.setSystemTime(Date.now() + 5 * 60 * 1000 + 1);
        await cachedPost('/api/x', { id: 1 });

        expect(fetch).toHaveBeenCalledTimes(2);
    });

    it('forgets a failed request, so the next one retries', async () => {
        fetch.mockImplementationOnce(() => Promise.resolve({ ok: false, status: 500 }));

        await expect(cachedPost('/api/x', { id: 1 })).rejects.toThrow('failed (500)');
        await expect(cachedPost('/api/x', { id: 1 })).resolves.toEqual({ name: 'ENGM' });
    });
});
