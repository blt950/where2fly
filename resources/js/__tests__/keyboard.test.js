// @vitest-environment jsdom
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { watchKeyboard } from '../keyboard';

let viewport;
let stop;

const resize = (width, height) => {
    Object.assign(viewport, { width, height });
    viewport.dispatchEvent(new Event('resize'));
};
const open = () => document.body.classList.contains('keyboard-open');
const field = (html) => {
    document.body.insertAdjacentHTML('beforeend', html);
    return document.body.lastElementChild;
};

beforeEach(() => {
    vi.useFakeTimers();
    viewport = Object.assign(new EventTarget(), { width: 390, height: 800 });
    window.visualViewport = viewport;
    stop = watchKeyboard(window);
});

afterEach(() => {
    stop();
    vi.useRealTimers();
    document.body.innerHTML = '';
    document.body.className = '';
    delete window.visualViewport;
});

describe('watchKeyboard', () => {
    it('marks the keyboard open while a text field has focus and the view has shrunk', () => {
        field('<input type="text">').focus();
        expect(open()).toBe(false);

        resize(390, 450);
        expect(open()).toBe(true);
    });

    it('lets go when the keyboard closes but the field keeps focus (Android back button)', () => {
        field('<input type="search">').focus();
        resize(390, 450);

        resize(390, 800);
        expect(open()).toBe(false);
    });

    it('lets go on blur, but not between two fields', () => {
        const first = field('<input type="text">');
        const second = field('<textarea></textarea>');
        first.focus();
        resize(390, 450);

        second.focus();
        vi.runAllTimers();
        expect(open()).toBe(true);

        second.blur();
        vi.runAllTimers();
        expect(open()).toBe(false);
    });

    it('ignores fields without a keyboard, and a hardware keyboard that shrinks nothing', () => {
        field('<input type="checkbox">').focus();
        resize(390, 450);
        expect(open()).toBe(false);

        resize(390, 800);
        field('<input type="text">').focus();
        expect(open()).toBe(false);
    });

    it('starts over after a rotation instead of comparing with the other orientation', () => {
        resize(844, 390);
        field('<input type="text">').focus();
        expect(open()).toBe(false);

        resize(844, 200);
        expect(open()).toBe(true);
    });
});
