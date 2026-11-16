// The query a native GET submit of this form would produce, so bookmarked URLs keep their shape
export const formUrl = (form) => `${form.action}?${new URLSearchParams(new FormData(form)).toString()}`;

// The form's fields shaped like Laravel's old input (`a[]` arrays, `a[key]` maps). Unchecked
// boxes are absent from a form post, so `uncheckedAs` names the ones to record as '0'.
export const formToOld = (form, uncheckedAs = []) => {
    const old = {};

    for (const [key, value] of new FormData(form)) {
        const [, name, sub] = key.match(/^([^[]+)\[([^\]]*)\]$/) ?? [];

        if (name === undefined) {
            old[key] = value;
        } else if (sub === '') {
            (old[name] ??= []).push(value);
        } else {
            (old[name] ??= {})[sub] = value;
        }
    }

    uncheckedAs.forEach((name) => { old[name] ??= '0'; });

    return old;
};
