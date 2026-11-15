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
    const onVisibleChange = vi.fn();
    const utils = render(
        <Drawer enabled stop="half" onStopChange={onStopChange} onVisibleChange={onVisibleChange} {...props}>
            <input id="field" />
            <input id="box" type="checkbox" />
        </Drawer>,
    );

    return { ...utils, onStopChange, onVisibleChange, handle: utils.container.querySelector('.drawer-handle') };
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
        const { container, onVisibleChange } = renderDrawer();

        expect(container.querySelector('main').style.getPropertyValue('--drawer-y')).toBe('400px');
        expect(onVisibleChange).toHaveBeenLastCalledWith(400);
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
        const { container, onVisibleChange } = renderDrawer({ enabled: false });

        expect(container.querySelector('.drawer-handle')).toBeNull();
        expect(container.querySelector('main').className).toBe('');
        expect(onVisibleChange).toHaveBeenLastCalledWith(0);
    });
});
