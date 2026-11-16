// @vitest-environment jsdom
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { watchHistoryRestores } from '../components/shell/historyRestore';

const page = (account, messages = []) => ({ props: { nav: { account: account ? { label: account } : null }, alerts: { messages, verification: null } } });

let router;
let navigate;

beforeEach(() => {
    vi.useFakeTimers();
    router = { on: vi.fn((name, handler) => { navigate = (detail) => handler({ detail }); }), reload: vi.fn(), replace: vi.fn() };
    window.umami = { track: vi.fn() };
    watchHistoryRestores(router);
});

afterEach(() => {
    vi.useRealTimers();
    delete window.umami;
});

describe('watchHistoryRestores', () => {
    it('leaves a restored entry alone when the login is the same and it has no flash messages', () => {
        navigate({ type: 'visit', page: page('pilot') });
        navigate({ type: 'history', page: page('pilot') });

        expect(router.reload).not.toHaveBeenCalled();
        expect(router.replace).not.toHaveBeenCalled();
    });

    it('asks for the session props again when the entry is from before a login or logout', () => {
        navigate({ type: 'initial', page: page(null) });
        navigate({ type: 'visit', page: page('pilot') });
        navigate({ type: 'history', page: page(null) });

        expect(router.reload).toHaveBeenCalledWith({ only: ['nav', 'alerts', 'mapHint', 'csrfToken', 'listsRevision'] });
    });

    it('drops the flash messages a restored entry already showed, and keeps the verification notice', () => {
        navigate({ type: 'visit', page: page('pilot') });
        navigate({ type: 'history', page: page('pilot', [{ key: 'success', text: 'List updated' }]) });

        const visit = router.replace.mock.calls[0][0];
        expect(visit).toMatchObject({ preserveState: true, preserveScroll: true });
        expect(visit.props({ other: 1, alerts: { messages: [{ key: 'success' }], verification: { email: 'a@b.c' } } }))
            .toEqual({ other: 1, alerts: { messages: [], verification: { email: 'a@b.c' } } });
    });

    it('sends the page view for Back and Forward, which umami never sees, once the title is set', () => {
        window.history.replaceState(null, '', '/top?continent=EU');
        document.title = 'Where2Fly | Top List';

        navigate({ type: 'visit', page: page(null) });
        vi.advanceTimersByTime(300);
        expect(window.umami.track).not.toHaveBeenCalled();

        navigate({ type: 'history', page: page(null) });
        vi.advanceTimersByTime(300);
        expect(window.umami.track.mock.calls[0][0]({ website: 'w', url: '/old' }))
            .toEqual({ website: 'w', url: '/top?continent=EU', title: 'Where2Fly | Top List' });
    });

    it('asks again when the browser brings a whole page back from its cache', () => {
        window.dispatchEvent(Object.assign(new Event('pageshow'), { persisted: false }));
        expect(router.reload).not.toHaveBeenCalled();

        window.dispatchEvent(Object.assign(new Event('pageshow'), { persisted: true }));
        expect(router.reload).toHaveBeenCalled();
    });
});
