// @vitest-environment jsdom
import { cleanup, fireEvent, render, screen, waitFor } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const sentry = { client: {}, sendFeedback: vi.fn() };

vi.mock('@inertiajs/react', () => ({ Head: () => null, Link: ({ children, ...props }) => <a {...props}>{children}</a> }));
vi.mock('@sentry/react', () => ({ getClient: () => sentry.client, sendFeedback: (...args) => sentry.sendFeedback(...args) }));

const { default: ErrorPage } = await import('../pages/Error');

beforeEach(() => {
    vi.stubGlobal('route', (name) => name);
    sentry.client = {};
    sentry.sendFeedback = vi.fn(() => Promise.resolve());
});

afterEach(() => {
    cleanup();
    vi.unstubAllGlobals();
});

describe('Error page', () => {
    it('asks what happened under a 500, and attaches it to the crash\'s Sentry event', async () => {
        render(<ErrorPage status={500} sentryEventId="abc123" />);

        fireEvent.change(screen.getByLabelText('Tell us what happened'), { target: { value: 'I pressed search' } });
        fireEvent.click(screen.getByRole('button', { name: 'Send report' }));

        expect(sentry.sendFeedback).toHaveBeenCalledWith({ message: 'I pressed search', associatedEventId: 'abc123' });
        await screen.findByText('Thank you for helping us!');
    });

    it('says so when the report could not be sent, and keeps the text', async () => {
        sentry.sendFeedback = vi.fn(() => Promise.reject(new Error('blocked')));
        vi.spyOn(console, 'error').mockImplementation(() => {});
        render(<ErrorPage status={500} sentryEventId="abc123" />);

        fireEvent.change(screen.getByLabelText('Tell us what happened'), { target: { value: 'It broke' } });
        fireEvent.click(screen.getByRole('button', { name: 'Send report' }));

        await waitFor(() => expect(screen.getByRole('alert').textContent).toContain('could not send'));
        expect(screen.getByLabelText('Tell us what happened').value).toBe('It broke');
    });

    it('asks nothing for other errors, without an event, or without Sentry', () => {
        const { rerender } = render(<ErrorPage status={404} sentryEventId="abc123" />);
        expect(screen.queryByLabelText('Tell us what happened')).toBeNull();

        rerender(<ErrorPage status={500} sentryEventId={null} />);
        expect(screen.queryByLabelText('Tell us what happened')).toBeNull();

        sentry.client = undefined;
        rerender(<ErrorPage status={500} sentryEventId="abc123" />);
        expect(screen.queryByLabelText('Tell us what happened')).toBeNull();
    });
});
