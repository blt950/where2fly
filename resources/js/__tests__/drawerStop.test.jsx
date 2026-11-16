// @vitest-environment jsdom
import { act, renderHook } from '@testing-library/react';
import { afterEach, describe, expect, it } from 'vitest';

import { useDrawerStop } from '../components/shell/useDrawerStop';

const Results = () => null;
const Form = () => null;
const Text = () => null;
Text.drawer = 'full';
const Kept = () => null;
Kept.drawer = 'keep';

const mount = (props) => renderHook((p) => useDrawerStop(p), {
    initialProps: { mode: 'sheet', Page: Form, sceneVersion: 1, selectedCard: null, ...props },
});

afterEach(() => document.body.classList.remove('shell-compact'));

describe('useDrawerStop', () => {
    it('brings the map back with the results after a search from a raised form', () => {
        const { result, rerender } = mount();
        act(() => result.current.setStop('full'));

        rerender({ mode: 'sheet', Page: Results, sceneVersion: 2, selectedCard: null });
        expect(result.current.stop).toBe('half');
    });

    it('settles at half on new content for the same page, e.g. another filter', () => {
        const { result, rerender } = mount({ Page: Results });
        act(() => result.current.setStop('full'));

        rerender({ mode: 'sheet', Page: Results, sceneVersion: 2, selectedCard: null });
        expect(result.current.stop).toBe('half');
    });

    it('opens reading pages fully, and leaves full behind for a map page after them', () => {
        const { result, rerender } = mount();

        rerender({ mode: 'sheet', Page: Text, sceneVersion: 1, selectedCard: null });
        expect(result.current.stop).toBe('full');

        rerender({ mode: 'sheet', Page: Results, sceneVersion: 1, selectedCard: null });
        expect(result.current.stop).toBe('half');
    });

    it('raises a collapsed drawer on request, to the page\'s own stop', () => {
        document.body.classList.add('shell-compact');
        const { result } = mount({ Page: Text });

        act(() => result.current.onBackgroundClick());
        expect(result.current.stop).toBe('peek');

        act(() => result.current.raiseDrawer());
        expect(result.current.stop).toBe('full');
    });

    it('settles at half when an airport is selected, and moves nothing on wide screens', () => {
        const { result, rerender } = mount({ Page: Text });
        rerender({ mode: 'sheet', Page: Text, sceneVersion: 1, selectedCard: 7 });
        expect(result.current.stop).toBe('half');

        const wide = mount({ mode: 'panel' });
        wide.rerender({ mode: 'panel', Page: Text, sceneVersion: 2, selectedCard: 3 });
        expect(wide.result.current.stop).toBe('half');
    });

    it('keeps results as the search left the drawer, raised or half, but never collapsed', () => {
        const { result, rerender } = mount();
        act(() => result.current.setStop('full'));

        rerender({ mode: 'sheet', Page: Kept, sceneVersion: 2, selectedCard: null });
        expect(result.current.stop).toBe('full');

        // Choosing an airport still brings the map back for its route
        rerender({ mode: 'sheet', Page: Kept, sceneVersion: 2, selectedCard: 5 });
        expect(result.current.stop).toBe('half');

        act(() => result.current.setStop('peek'));
        rerender({ mode: 'sheet', Page: Kept, sceneVersion: 3, selectedCard: 5 });
        expect(result.current.stop).toBe('half');
    });
});
