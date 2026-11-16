// @vitest-environment jsdom
import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { MapLayoutContext } from '../components/context/MapLayoutContext';

vi.mock('@inertiajs/react', () => ({
    Head: () => null,
    // eslint-disable-next-line no-unused-vars
    Link: ({ children, preserveScroll, ...props }) => <a {...props}>{children}</a>,
    router: { post: vi.fn() },
}));
vi.mock('@sentry/react', () => ({ sendFeedback: vi.fn() }));

const { default: Feedback } = await import('../pages/Feedback');
const { sendFeedback } = await import('@sentry/react');

const author = { login: 'pilot', avatar: 'a.png', created: '1 day ago' };
const issues = [{ number: 7, title: 'Night mode', votes: 2, isNew: false, voted: false, author }];
const issue = { number: 7, title: 'Night mode', votes: 2, voted: false, html: '<p>Please add it</p>', author, url: 'https://github.com/x/7', comments: [] };

const renderPage = (props, { compact }) => {
    const wideSlot = document.createElement('div');
    document.body.append(wideSlot);
    render(
        <MapLayoutContext.Provider value={{ compact, wideSlot: compact ? null : wideSlot, getInsets: () => ({}) }}>
            <Feedback issues={issues} issue={null} canVote={false} loginUrl="login" bugReports={false} {...props} />
        </MapLayoutContext.Provider>,
    );

    return wideSlot;
};

beforeEach(() => {
    vi.stubGlobal('route', (name, params) => `${name}${params ? `/${JSON.stringify(params)}` : ''}`);
});

afterEach(() => {
    cleanup();
    document.body.innerHTML = '';
    vi.unstubAllGlobals();
});

describe('Feedback', () => {
    it('puts the chosen idea in the wide panel over the map, beside the list', () => {
        const wideSlot = renderPage({ issue }, { compact: false });

        expect(wideSlot.textContent).toContain('Please add it');
        expect(screen.getByText('Night mode', { selector: '.card-title' })).toBeTruthy();
        expect(screen.getByText('Log in to vote')).toBeTruthy();
    });

    it('puts the bug report modal on <body>, outside the drawer that would clip it', () => {
        const show = vi.fn();
        vi.stubGlobal('bootstrap', { Modal: { getOrCreateInstance: () => ({ show, dispose: vi.fn() }) } });

        renderPage({ bugReports: true }, { compact: true });
        const modal = document.getElementById('bugReportModal');

        expect(modal.parentElement).toBe(document.body);
        fireEvent.click(screen.getByRole('button', { name: /Report a bug/ }));
        expect(show).toHaveBeenCalled();
    });

    it('sends the report through Sentry and thanks the reporter', async () => {
        vi.stubGlobal('bootstrap', { Modal: { getOrCreateInstance: () => ({ show: vi.fn(), dispose: vi.fn() }) } });
        sendFeedback.mockResolvedValue('event-id');
        renderPage({ bugReports: true }, { compact: true });

        fireEvent.click(screen.getByText('Send report'));
        // The modal is aria-hidden until Bootstrap shows it, so query by text, not by role
        expect(screen.getByText('Please describe what went wrong.')).toBeTruthy();
        expect(sendFeedback).not.toHaveBeenCalled();

        fireEvent.change(screen.getByLabelText('What went wrong?'), { target: { value: '  The map is blank  ' } });
        fireEvent.click(screen.getByText('Send report'));

        expect(sendFeedback).toHaveBeenCalledWith({ message: 'The map is blank' });
        expect(await screen.findByText(/Thank you for reporting/)).toBeTruthy();
    });

    it('asks for a choice in the wide panel when no idea is open', () => {
        const wideSlot = renderPage({}, { compact: false });

        expect(wideSlot.textContent).toContain('Choose an idea from the sidebar');
    });

    it('shows the list or the idea in the drawer on the compact layout, not both', () => {
        renderPage({}, { compact: true });
        expect(screen.getByText('Night mode', { selector: '.card-title' })).toBeTruthy();
        expect(screen.queryByText('Choose an idea from the sidebar for details and actions')).toBeNull();
        cleanup();

        renderPage({ issue }, { compact: true });
        expect(screen.queryByText('Night mode', { selector: '.card-title' })).toBeNull();
        expect(screen.getByText('Please add it')).toBeTruthy();
        expect(screen.getByText('All ideas')).toBeTruthy();
    });
});
