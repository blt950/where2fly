import { useEffect, useLayoutEffect, useRef } from 'react';

const el = (tag, props = {}, children = []) => {
    const node = Object.assign(document.createElement(tag), props);
    node.append(...children);
    return node;
};

const labelOf = (items, value) => items.find((item) => item.value === value)?.label;

// u-combobox rewrites its own children (selected <data> chips, the <select> it submits), so React
// must never own them. The element is built once, detached, then inserted, exactly as the Blade
// markup arrived parsed; React only renders the host. `items` are { value, label } or { divider }.
const Combobox = ({
    id, name, items, selected = [], placeholder, taller = false, disabled = false, hidden = false,
    exclusive = [], onChange,
}) => {

    const hostRef = useRef(null);
    const comboboxRef = useRef(null);
    const onChangeRef = useRef(onChange);
    onChangeRef.current = onChange;

    useLayoutEffect(() => {
        const options = items.filter((item) => !item.divider);
        const input = el('input', { id: `${id}-input`, placeholder });
        input.setAttribute('list', `${id}-list`);

        const datalist = el('u-datalist', { id: `${id}-list`, className: taller ? 'taller' : '', tabIndex: -1, hidden: true },
            items.map((item) => (item.divider
                ? el('div', { className: 'divider', textContent: item.divider })
                : el('u-option', { value: item.value, textContent: item.label }))));

        const chips = selected
            .filter((value) => labelOf(options, value) !== undefined)
            .map((value) => el('data', { value, textContent: labelOf(options, value) }));

        const combobox = el('u-combobox', { id }, [el('select', { name, multiple: true }), ...chips, input, datalist]);
        combobox.setAttribute('data-multiple', '');

        const values = () => [...combobox.querySelectorAll('data')].map((chip) => chip.value);

        // "Anywhere"/"Domestic" override every other area server side, so they stand alone
        const syncExclusive = () => {
            if (!exclusive.length) return;

            const chosen = values();
            const exclusiveChosen = chosen.some((value) => exclusive.includes(value));
            const otherChosen = chosen.some((value) => !exclusive.includes(value));

            datalist.querySelectorAll('u-option').forEach((option) => {
                option.disabled = exclusiveChosen ? !chosen.includes(option.value) : otherChosen && exclusive.includes(option.value);
            });
            // A disabled option is hidden by u-datalist, so its heading would be left dangling
            datalist.querySelectorAll('.divider').forEach((divider) => { divider.hidden = exclusiveChosen; });
        };

        const syncPlaceholder = () => { input.placeholder = values().length ? 'Choose' : placeholder; };

        // Open from a click anywhere on the element, not just the input
        combobox.addEventListener('click', (event) => { if (event.target === combobox) input.click(); });

        combobox.addEventListener('comboboxafterselect', () => {
            input.value = null;
            input.focus();
            // u-combobox updates its chips after this event, so read them on the next tick
            setTimeout(() => {
                input.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
                syncExclusive();
                syncPlaceholder();
                onChangeRef.current?.(values());
            });
        });

        syncExclusive();
        syncPlaceholder();
        hostRef.current.replaceChildren(combobox);
        comboboxRef.current = combobox;

        return () => { combobox.remove(); comboboxRef.current = null; };
        // Built once per mount; the page remounts on every visit.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    // Disabled while hidden, so a stale selection is neither submitted nor counted in metrics
    useEffect(() => {
        const combobox = comboboxRef.current;
        combobox.classList.toggle('d-none', hidden);
        combobox.querySelectorAll('input, select').forEach((field) => { field.disabled = disabled; });
    }, [disabled, hidden]);

    return <div ref={hostRef} style={{ display: 'contents' }} />;
};

export default Combobox;
