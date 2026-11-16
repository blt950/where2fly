// @vitest-environment jsdom
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { installTooltips } from '../components/utils/tooltips';

let created;
let instances;

beforeEach(() => {
    instances = new Map();
    created = [];

    // Bootstrap's part: one delegating instance on <body>, and an instance per trigger once shown
    const Tooltip = vi.fn(function Tooltip(element, options) {
        this.dispose = vi.fn();
        created.push({ element, options, instance: this });
    });
    Tooltip.getInstance = (element) => instances.get(element);
    vi.stubGlobal('bootstrap', { Tooltip });
});

afterEach(() => {
    document.body.innerHTML = '';
    vi.unstubAllGlobals();
});

const trigger = () => {
    const element = document.createElement('span');
    element.dataset.bsToggle = 'tooltip';
    document.body.append(element);
    instances.set(element, { dispose: vi.fn() });

    return element;
};

const fire = (element, name) => element.dispatchEvent(new CustomEvent(name, { bubbles: true }));
const mutations = () => new Promise((resolve) => { setTimeout(resolve, 0); });

describe('installTooltips', () => {
    it('delegates from <body> to every trigger, rendered now or later', () => {
        const uninstall = installTooltips();

        expect(created).toHaveLength(1);
        expect(created[0].element).toBe(document.body);
        expect(created[0].options).toMatchObject({ selector: '[data-bs-toggle="tooltip"]', container: 'body', animation: false });

        uninstall();
        expect(created[0].instance.dispose).toHaveBeenCalled();
    });

    it('takes down a tip whose trigger was removed while it showed', async () => {
        const uninstall = installTooltips();
        const element = trigger();

        fire(element, 'shown.bs.tooltip');
        element.remove();
        await mutations();

        expect(instances.get(element).dispose).toHaveBeenCalledTimes(1);
        uninstall();
    });

    it('leaves a tip alone while its trigger is there, and one that already hid', async () => {
        const uninstall = installTooltips();
        const shown = trigger();
        const hidden = trigger();

        fire(shown, 'shown.bs.tooltip');
        document.body.append(document.createElement('div'));
        await mutations();
        expect(instances.get(shown).dispose).not.toHaveBeenCalled();

        fire(hidden, 'shown.bs.tooltip');
        fire(hidden, 'hidden.bs.tooltip');
        hidden.remove();
        await mutations();
        expect(instances.get(hidden).dispose).not.toHaveBeenCalled();
        uninstall();
    });
});
