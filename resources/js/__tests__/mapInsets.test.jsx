// @vitest-environment jsdom
import { act, renderHook } from '@testing-library/react';
import { describe, expect, it } from 'vitest';

import { useMapInsets } from '../components/shell/useMapInsets';

const boxAt = (rect, offsetHeight = 0) => ({ getBoundingClientRect: () => rect, offsetHeight });

describe('useMapInsets', () => {
    it('measures the card stack and footer it was handed on wide screens, at the moment it is asked', () => {
        const { result } = renderHook(() => useMapInsets('panel'));
        result.current.mapRef.current = boxAt({ left: 400 });

        expect(result.current.getInsets()).toEqual({ top: 0, right: 0, bottom: 0, left: 0 });

        act(() => {
            result.current.registerCards(boxAt({ right: 820 }));
            result.current.registerFooter(boxAt({}, 30));
        });
        expect(result.current.getInsets()).toEqual({ top: 0, right: 0, bottom: 30, left: 420 });

        act(() => result.current.registerCards(null));
        expect(result.current.getInsets().left).toBe(0);
    });

    it('takes what the drawer reports on the compact layouts, bottom as a sheet or left docked', () => {
        const { result, rerender } = renderHook(({ mode }) => useMapInsets(mode), { initialProps: { mode: 'sheet' } });

        act(() => result.current.onCoverChange({ bottom: 380, left: 0 }));
        expect(result.current.getInsets()).toEqual({ top: 0, right: 0, bottom: 380, left: 0 });

        rerender({ mode: 'side' });
        act(() => result.current.onCoverChange({ bottom: 0, left: 350 }));
        expect(result.current.getInsets()).toEqual({ top: 0, right: 0, bottom: 0, left: 350 });
    });
});
