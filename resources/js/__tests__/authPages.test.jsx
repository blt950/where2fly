// @vitest-environment jsdom
import { useState } from 'react';
import { act, cleanup, fireEvent, render, screen, waitFor } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const posts = [];

// A small stand-in for Inertia's form helper: state, setData, reset and a recorded post
vi.mock('@inertiajs/react', () => ({
    Head: () => null,
    Link: ({ children, ...props }) => <a {...props}>{children}</a>,
    useForm: (initial) => {
        const [data, setState] = useState(initial);
        return {
            data,
            errors: {},
            processing: false,
            setData: (name, value) => setState((current) => ({ ...current, [name]: value })),
            reset: (...names) => setState((current) => ({ ...current, ...Object.fromEntries(names.map((name) => [name, initial[name]])) })),
            post: (url, options) => posts.push({ url, data, options }),
        };
    },
}));

const { default: Login } = await import('../pages/Login');

let widget;

beforeEach(() => {
    posts.length = 0;
    vi.stubGlobal('route', (name) => name);
    widget = { render: vi.fn((element, options) => { widget.options = options; return 'w1'; }), reset: vi.fn(), remove: vi.fn() };
    window.turnstile = widget;
});

afterEach(() => {
    cleanup();
    delete window.turnstile;
    vi.unstubAllGlobals();
});

describe('Login', () => {
    it('renders the challenge with the site key and sends its token with the form', async () => {
        render(<Login turnstileKey="site-key" old={{ username: 'pilot' }} />);
        await waitFor(() => expect(widget.render).toHaveBeenCalled());
        expect(widget.options).toMatchObject({ sitekey: 'site-key', action: 'login' });

        act(() => widget.options.callback('token-1'));
        fireEvent.change(screen.getByLabelText('Password'), { target: { value: 'secret' } });
        fireEvent.click(screen.getByRole('button', { name: 'LOGIN' }));

        expect(posts[0]).toMatchObject({ url: 'user.login', data: { username: 'pilot', password: 'secret', 'cf-turnstile-response': 'token-1' } });
    });

    it('clears the password and asks for a fresh challenge after a failed attempt', async () => {
        render(<Login turnstileKey="site-key" old={{}} />);
        await waitFor(() => expect(widget.render).toHaveBeenCalled());
        act(() => widget.options.callback('token-1'));
        fireEvent.change(screen.getByLabelText('Password'), { target: { value: 'wrong' } });
        fireEvent.click(screen.getByRole('button', { name: 'LOGIN' }));

        act(() => posts[0].options.onFinish());

        expect(screen.getByLabelText('Password').value).toBe('');
        expect(widget.reset).toHaveBeenCalledWith('w1');
    });

    it('takes the widget down when the page goes', async () => {
        const { unmount } = render(<Login turnstileKey="site-key" old={{}} />);
        await waitFor(() => expect(widget.render).toHaveBeenCalled());

        unmount();
        expect(widget.remove).toHaveBeenCalledWith('w1');
    });
});
