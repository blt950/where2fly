// @vitest-environment jsdom
import { useState } from 'react';
import { cleanup, fireEvent, render, screen, waitFor } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const posts = [];
const gets = [];
let formErrors = {};

// A small stand-in for Inertia's form helper: state, setData (a field or the whole form), reset
// and a recorded post
vi.mock('@inertiajs/react', () => ({
    Head: () => null,
    Link: ({ children, ...props }) => <a {...props}>{children}</a>,
    router: { get: (...args) => gets.push(args) },
    useForm: (initial) => {
        const [data, setState] = useState(initial);
        return {
            data,
            errors: formErrors,
            processing: false,
            setData: (name, value) => setState((current) => (typeof name === 'string' ? { ...current, [name]: value } : name)),
            reset: (...names) => setState((current) => ({ ...current, ...Object.fromEntries(names.map((name) => [name, initial[name]])) })),
            post: (url, options) => posts.push({ url, data, options }),
        };
    },
}));

const { default: Admin } = await import('../pages/Admin');
const { default: ListForm } = await import('../pages/ListForm');
const { default: PasswordReset } = await import('../pages/PasswordReset');
const { default: Register } = await import('../pages/Register');
const { default: SceneryCreate } = await import('../pages/SceneryCreate');
const { default: SceneryEdit } = await import('../pages/SceneryEdit');

const type = (label, value) => fireEvent.change(screen.getByLabelText(label), { target: { value } });
const SIMULATORS = [{ id: 1, name: 'MSFS 2024' }, { id: 2, name: 'X-Plane 12' }];
const EXISTING = [
    { id: 5, simulator: 'MSFS2024', payware: 1, developer: 'Aerosoft', published: true, current: false },
    { id: 6, simulator: 'XP12', payware: 0, developer: 'Community', published: false, current: false },
    { id: 7, simulator: 'MSFS2020', payware: -1, developer: 'Asobo', published: true, current: true },
];

beforeEach(() => {
    posts.length = 0;
    gets.length = 0;
    formErrors = {};
    vi.stubGlobal('route', (name, params) => (params === undefined ? name : `${name}/${params}`));
});

afterEach(() => {
    cleanup();
    delete window.turnstile;
    vi.unstubAllGlobals();
});

describe('Register', () => {
    it('sends the account with the challenge token, and clears the passwords after an error', async () => {
        const widget = { render: vi.fn((element, options) => { widget.options = options; return 'w1'; }), reset: vi.fn(), remove: vi.fn() };
        window.turnstile = widget;

        render(<Register turnstileKey="site-key" old={{ username: 'pilot', email: 'pilot@example.com' }} />);
        await waitFor(() => expect(widget.render).toHaveBeenCalled());
        expect(widget.options).toMatchObject({ sitekey: 'site-key', action: 'register' });

        type('Password', 'secret-1');
        type('Confirm Password', 'secret-1');
        fireEvent.click(screen.getByLabelText(/I accept/));
        fireEvent.click(screen.getByRole('button', { name: 'REGISTER' }));

        expect(posts[0]).toMatchObject({ url: 'user.register', data: {
            username: 'pilot', email: 'pilot@example.com', password: 'secret-1', password_confirmation: 'secret-1', privacy_policy: true,
        } });

        posts[0].options.onError();
        await waitFor(() => expect(screen.getByLabelText('Password').value).toBe(''));
        expect(screen.getByLabelText('Username').value).toBe('pilot');
    });
});

describe('PasswordReset', () => {
    it('posts the token from the link with the new password, and shows a token error', () => {
        formErrors = { token: 'This password reset token is invalid.' };
        render(<PasswordReset token="abc" email="pilot@example.com" />);

        type('New password', 'secret-2');
        type('Confirm new password', 'secret-2');
        fireEvent.click(screen.getByRole('button', { name: 'RESET PASSWORD' }));

        expect(posts[0]).toMatchObject({ url: 'password.update', data: { token: 'abc', email: 'pilot@example.com', password: 'secret-2' } });
        expect(screen.getByText('This password reset token is invalid.')).toBeTruthy();
    });
});

describe('ListForm', () => {
    const simulators = [{ id: 3, name: 'MSFS' }, { id: 4, name: 'XP' }];

    it('creates a list from a blank form with the first simulator chosen', () => {
        render(<ListForm list={null} simulators={simulators} canMakePublic={false} old={{}} />);

        type('Name', 'Favourites');
        type('Airports', 'ENGM\nESSA');
        fireEvent.click(screen.getByRole('button', { name: 'Create list' }));

        expect(posts[0]).toMatchObject({ url: 'list.store', data: { name: 'Favourites', simulator: '3', airports: 'ENGM\nESSA', public: false } });
        expect(screen.queryByLabelText(/Public/)).toBeNull();
        expect(screen.queryByText(/Delete list/)).toBeNull();
    });

    it('edits a list, old input from a failed save winning over what is stored', () => {
        const list = { id: 9, name: 'Stored', color: '#112233', simulator: 4, airports: 'ENGM', public: true };
        render(<ListForm list={list} simulators={simulators} canMakePublic old={{ name: 'Typed' }} />);

        expect(screen.getByLabelText('Name').value).toBe('Typed');
        expect(screen.getByLabelText('Simulator').value).toBe('4');
        expect(screen.getByLabelText(/Public/).checked).toBe(true);

        fireEvent.click(screen.getByRole('button', { name: /UPDATE/ }));
        expect(posts[0].url).toBe('list.update/9');
    });

    it('asks before deleting, and stays when the answer is no', () => {
        vi.spyOn(window, 'confirm').mockReturnValue(false);
        render(<ListForm list={{ id: 9, name: 'Stored', airports: '' }} simulators={simulators} canMakePublic={false} old={{}} />);

        const allowed = fireEvent.click(screen.getByText(/Delete list/));

        expect(window.confirm).toHaveBeenCalled();
        expect(allowed).toBe(false);
    });
});

describe('Admin', () => {
    it('lists the stats and each contribution with its review link', () => {
        render(<Admin stats={{ users: 12, lists: 3, sceneries: 40 }}
            contributions={[{ id: 7, simulator: 'MSFS2024', icao: 'EDDM', suggestedBy: 'pilot' }]} />);

        expect(screen.getByText('Users: 12')).toBeTruthy();
        expect(screen.getByRole('link', { name: 'EDDM' }).getAttribute('href')).toBe('scenery.edit/7');
        expect(screen.getByText('by pilot')).toBeTruthy();
    });

    it('says so when nothing awaits review', () => {
        render(<Admin stats={{ users: 1, lists: 0, sceneries: 0 }} contributions={[]} />);

        expect(screen.getByText('No scenery contributions')).toBeTruthy();
    });
});

describe('SceneryCreate', () => {
    it('submits the contribution with the chosen simulators', () => {
        render(<SceneryCreate icao="EDDM" simulators={SIMULATORS} existing={null} />);

        type('Developer', 'Aerosoft');
        type(/^Link/, 'https://example.com/eddm');
        type('Payware', '1');
        fireEvent.click(screen.getByLabelText('X-Plane 12'));
        fireEvent.click(screen.getByLabelText('MSFS 2024'));
        fireEvent.click(screen.getByLabelText('MSFS 2024'));
        fireEvent.click(screen.getByRole('button', { name: 'Submit Contribution' }));

        expect(posts[0]).toMatchObject({ url: 'scenery.store', data: {
            icao: 'EDDM', developer: 'Aerosoft', link: 'https://example.com/eddm', payware: '1', simulators: [2],
        } });
    });

    it('starts a blank form once a contribution went through', async () => {
        render(<SceneryCreate icao="EDDM" simulators={SIMULATORS} existing={null} />);
        type('Developer', 'Aerosoft');
        type(/^Link/, 'https://example.com/eddm');
        type('Payware', '0');
        fireEvent.click(screen.getByRole('button', { name: 'Submit Contribution' }));

        posts[0].options.onSuccess();

        await waitFor(() => expect(screen.getByLabelText('Developer').value).toBe(''));
        expect(screen.getByLabelText('ICAO').value).toBe('');
    });

    it('looks the airport up on leaving the ICAO field, asking only for what it already has', () => {
        render(<SceneryCreate icao="" simulators={SIMULATORS} existing={null} />);

        type('ICAO', 'eddf');
        fireEvent.blur(screen.getByLabelText('ICAO'));

        expect(gets[0][0]).toBe('scenery.create');
        expect(gets[0][1]).toEqual({ icao: 'EDDF' });
        expect(gets[0][2]).toMatchObject({ only: ['icao', 'existing'], preserveState: true });

        // Nothing typed, or the airport already looked up: no request
        cleanup();
        gets.length = 0;
        render(<SceneryCreate icao="EDDF" simulators={SIMULATORS} existing={[]} />);
        fireEvent.blur(screen.getByLabelText('ICAO'));
        expect(gets).toHaveLength(0);
    });

    it('lists what the airport already has, with its price model and review state', () => {
        render(<SceneryCreate icao="EDDM" simulators={SIMULATORS} existing={EXISTING} />);

        const rows = [...document.querySelectorAll('.alert-warning > div')].map((row) => row.textContent);
        expect(rows).toEqual(['MSFS2024 Payware Aerosoft', 'XP12 Freeware Community (Awaiting review)', 'MSFS2020 Included Asobo (This review)']);
    });
});

describe('SceneryEdit', () => {
    const scenery = { id: 7, simulator: 'MSFS2020', link: 'https://example.com/x', payware: 0, published: false, suggestedByUserId: 4, icao: 'EDDM', developer: 'Asobo' };

    it('fills the form from the scenery and posts the review', () => {
        render(<SceneryEdit scenery={scenery} existing={EXISTING} suggestedBy="pilot" />);

        expect(screen.getByText('Submitted by pilot')).toBeTruthy();
        expect(screen.getByLabelText('Payware').value).toBe('0');

        fireEvent.click(screen.getByLabelText(/Published/));
        fireEvent.click(screen.getByRole('button', { name: 'Save' }));

        expect(posts[0]).toMatchObject({ url: 'scenery.update/7', data: {
            suggested_by_user_id: 4, icao: 'EDDM', developer: 'Asobo', link: 'https://example.com/x', payware: '0', published: true,
        } });
    });

    it('makes the reviewer choose when the scenery is neither payware nor freeware', () => {
        render(<SceneryEdit scenery={{ ...scenery, payware: -1, suggestedByUserId: null }} existing={EXISTING} suggestedBy={null} />);

        expect(screen.getByLabelText('Payware').value).toBe('');
        expect(screen.getByText('Submitted by N/A')).toBeTruthy();
    });
});
