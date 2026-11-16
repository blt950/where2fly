// @vitest-environment jsdom
import { cleanup, fireEvent, render } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import Drawer from '../components/shell/Drawer';

const HEIGHT = 800;
const HANDLE = 44;

beforeEach(() => {
    vi.stubGlobal('ResizeObserver', class { observe() {} disconnect() {} });
    Object.defineProperty(HTMLElement.prototype, 'clientHeight', { configurable: true, get() { return this.tagName === 'MAIN' ? HEIGHT : 0; } });
    Object.defineProperty(HTMLElement.prototype, 'offsetHeight', { configurable: true, get() { return this.classList.contains('drawer-handle') ? HANDLE : 0; } });
    HTMLElement.prototype.setPointerCapture = vi.fn();
});

afterEach(() => {
    cleanup();
    vi.unstubAllGlobals();
});

const renderDrawer = (props = {}) => {
    const onStopChange = vi.fn();
    const onCoverChange = vi.fn();
    const utils = render(
        <Drawer mode="sheet" stop="half" onStopChange={onStopChange} onCoverChange={onCoverChange} {...props}>
            <input id="field" />
            <input id="box" type="checkbox" />
            <div id="list" style={{ overflowY: 'auto' }}><p id="row">row</p></div>
        </Drawer>,
    );

    return { ...utils, onStopChange, onCoverChange, handle: utils.container.querySelector('.drawer-handle') };
};

// fireEvent drops clientY/timeStamp on PointerEvent in jsdom, so build the events by hand
const pointer = (target, type, clientY, timeStamp) => {
    const event = new MouseEvent(type, { bubbles: true, clientY, button: 0 });
    Object.defineProperty(event, 'timeStamp', { value: timeStamp });
    Object.defineProperty(event, 'pointerId', { value: 1 });
    target.dispatchEvent(event);
};

describe('Drawer', () => {
    it('rests at the stop it is given and reports what it covers', () => {
        const { container, onCoverChange } = renderDrawer();

        expect(container.querySelector('main').style.getPropertyValue('--drawer-y')).toBe('400px');
        expect(onCoverChange).toHaveBeenLastCalledWith({ bottom: 400, left: 0 });
    });

    it('settles a slow drag on the nearest stop', () => {
        const { handle, onStopChange } = renderDrawer();

        pointer(handle, 'pointerdown', 400, 0);
        pointer(handle, 'pointermove', 500, 1000);
        pointer(handle, 'pointermove', 650, 2000);
        pointer(handle, 'pointerup', 650, 2100);

        expect(onStopChange).toHaveBeenCalledWith('peek');
    });

    it('moves a flick one stop in its direction', () => {
        const { handle, onStopChange } = renderDrawer();

        pointer(handle, 'pointerdown', 400, 0);
        pointer(handle, 'pointermove', 390, 10);
        pointer(handle, 'pointermove', 360, 20);
        pointer(handle, 'pointerup', 360, 25);

        expect(onStopChange).toHaveBeenCalledWith('full');
    });

    it('goes back where it was when the pointer is cancelled, without counting a tap', () => {
        const { container, handle, onStopChange } = renderDrawer();

        pointer(handle, 'pointerdown', 400, 0);
        pointer(handle, 'pointermove', 500, 100);
        pointer(handle, 'pointercancel', 500, 150);

        expect(onStopChange).not.toHaveBeenCalled();
        expect(container.querySelector('main').style.getPropertyValue('--drawer-y')).toBe('400px');
    });

    it('opens further on a tap and steps through stops from the keyboard', () => {
        const { handle, onStopChange } = renderDrawer({ stop: 'peek' });

        pointer(handle, 'pointerdown', 700, 0);
        pointer(handle, 'pointerup', 702, 50);
        expect(onStopChange).toHaveBeenLastCalledWith('half');

        fireEvent.click(handle, { detail: 0 });
        expect(onStopChange).toHaveBeenLastCalledWith('half');
    });

    it('raises itself fully when a text field takes focus, not for a checkbox', () => {
        const { container, onStopChange } = renderDrawer();

        fireEvent.focus(container.querySelector('#box'));
        expect(onStopChange).not.toHaveBeenCalled();

        fireEvent.focus(container.querySelector('#field'));
        expect(onStopChange).toHaveBeenCalledWith('full');
    });

    it('is a plain panel on wide screens', () => {
        const { container, onCoverChange } = renderDrawer({ mode: 'panel' });

        expect(container.querySelector('.drawer-handle')).toBeNull();
        expect(container.querySelector('main').className).toBe('');
        expect(onCoverChange).toHaveBeenLastCalledWith({ bottom: 0, left: 0 });
    });

    it('docks to the side on a phone turned sideways: no handle, no stops, it covers the left', () => {
        Object.defineProperty(HTMLElement.prototype, 'clientWidth', { configurable: true, get() { return this.tagName === 'MAIN' ? 350 : 0; } });
        const { container, onCoverChange } = renderDrawer({ mode: 'side' });

        expect(container.querySelector('.drawer-handle')).toBeNull();
        expect(container.querySelector('main').style.getPropertyValue('--drawer-y')).toBe('');
        expect(onCoverChange).toHaveBeenLastCalledWith({ bottom: 0, left: 350 });
        delete HTMLElement.prototype.clientWidth;
    });

    describe('dragged by its content (touch)', () => {
        const touch = (target, type, clientX, clientY, timeStamp) => {
            const event = new Event(type, { bubbles: true, cancelable: true });
            Object.defineProperty(event, 'touches', { value: type === 'touchend' ? [] : [{ clientX, clientY }] });
            Object.defineProperty(event, 'timeStamp', { value: timeStamp });
            target.dispatchEvent(event);
            return event;
        };

        it('follows a downward drag from the top and settles like the handle', () => {
            const { container, onStopChange } = renderDrawer();
            const row = container.querySelector('#row');

            touch(row, 'touchstart', 100, 450, 0);
            touch(row, 'touchmove', 102, 550, 1000);
            const move = touch(row, 'touchmove', 103, 700, 2000);
            touch(row, 'touchend', 0, 0, 2100);

            expect(move.defaultPrevented).toBe(true);
            expect(onStopChange).toHaveBeenCalledWith('peek');
        });

        it('raises the drawer on an upward drag while it is not fully open, as Google Maps does', () => {
            const { container, onStopChange } = renderDrawer();
            const row = container.querySelector('#row');

            touch(row, 'touchstart', 100, 450, 0);
            const move = touch(row, 'touchmove', 100, 380, 20);
            touch(row, 'touchmove', 100, 300, 40);
            touch(row, 'touchend', 0, 0, 45);

            expect(move.defaultPrevented).toBe(true);
            expect(onStopChange).toHaveBeenCalledWith('full');
        });

        it('leaves the content scrolling: upward when full, a scrolled list, a sideways swipe, a slider', () => {
            const { container, onStopChange } = renderDrawer({ stop: 'full' });
            const row = container.querySelector('#row');
            const gesture = (target, [x1, y1], [x2, y2]) => {
                touch(target, 'touchstart', x1, y1, 0);
                const move = touch(target, 'touchmove', x2, y2, 100);
                touch(target, 'touchend', 0, 0, 200);
                return move.defaultPrevented;
            };

            expect(gesture(row, [100, 450], [100, 350])).toBe(false);
            expect(gesture(row, [100, 450], [200, 470])).toBe(false);
            expect(gesture(container.querySelector('#field'), [100, 450], [100, 600])).toBe(false);

            container.querySelector('#list').scrollTop = 40;
            expect(gesture(row, [100, 450], [100, 600])).toBe(false);

            expect(onStopChange).not.toHaveBeenCalled();
        });

        it('steps on a wheel too, once per gesture: down from half to full, up from the top of full to half', () => {
            const wheel = (target, deltaY, timeStamp) => {
                const event = new Event('wheel', { bubbles: true, cancelable: true });
                Object.defineProperty(event, 'deltaY', { value: deltaY });
                Object.defineProperty(event, 'timeStamp', { value: timeStamp });
                target.dispatchEvent(event);
                return event;
            };

            const half = renderDrawer();
            expect(wheel(half.container.querySelector('#row'), 30, 1000).defaultPrevented).toBe(true);
            wheel(half.container.querySelector('#row'), 30, 1100);
            expect(half.onStopChange).toHaveBeenCalledTimes(1);
            expect(half.onStopChange).toHaveBeenCalledWith('full');
            cleanup();

            const full = renderDrawer({ stop: 'full' });
            expect(wheel(full.container.querySelector('#row'), 30, 1000).defaultPrevented).toBe(false);
            wheel(full.container.querySelector('#row'), -30, 2000);
            expect(full.onStopChange).toHaveBeenCalledWith('half');
    });
    });
});
