// @vitest-environment jsdom
import { readFileSync } from 'node:fs';
import { beforeEach, describe, expect, it } from 'vitest';

// The filter lives inline in the Blade partial (it must exist before umami's script runs), so
// the test runs that very script rather than a copy
const partial = readFileSync('resources/views/layouts/tracking.blade.php', 'utf8');
const script = partial.match(/<script>([\s\S]*?)<\/script>/)[1];

const view = (url, referrer = '') => window.w2fBeforeSend('event', { url, referrer, title: 't' });
const event = (name, url) => window.w2fBeforeSend('event', { name, url, referrer: '' });

beforeEach(() => {
    delete window.w2fBeforeSend;
    new Function(script)();
});

describe('w2fBeforeSend', () => {
    it('masks the ids of a list or scenery being edited, in the URL and the referrer', () => {
        expect(view('https://w2f.test/lists/42/edit', 'https://w2f.test/scenery/7/edit'))
            .toMatchObject({ url: 'https://w2f.test/lists/x/edit', referrer: 'https://w2f.test/scenery/x/edit' });
    });

    it('masks a password reset token and the email in its query', () => {
        expect(view('/account/recovery/reset/abc123?email=pilot%40example.com').url).toBe('/account/recovery/reset/x');
    });

    it('strips the selected airport, wherever it sits in the query', () => {
        expect(view('/search?icao=EGLL&airport=ENGM').url).toBe('/search?icao=EGLL');
        expect(view('/top?airport=ENGM').url).toBe('/top');
        expect(view('/search?airport=ENGM&icao=ESSA').url).toBe('/search?icao=ESSA');
    });

    it('counts opening, switching and closing a card as no page view', () => {
        expect(view('/search?icao=EGLL')).toBeTruthy();
        expect(view('/search?icao=EGLL&airport=ENGM')).toBe(false);
        expect(view('/search?icao=EGLL&airport=ESSA')).toBe(false);
        expect(view('/search?icao=EGLL')).toBe(false);
        expect(view('/top')).toBeTruthy();
    });

    it('lets every named event through, with the same masking', () => {
        view('/search?icao=EGLL');

        expect(event('Interactions', '/search?icao=EGLL&airport=ENGM')).toMatchObject({ name: 'Interactions', url: '/search?icao=EGLL' });
        expect(event('Interactions', '/search?icao=EGLL&airport=ENGM')).toBeTruthy();
    });

    it('passes an empty payload on untouched', () => {
        expect(window.w2fBeforeSend('event', undefined)).toBeUndefined();
    });
});
