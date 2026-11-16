// Marks `body.keyboard-open` while someone types on an on-screen keyboard, so the bottom nav can
// step aside (shell.scss). Both must hold: a text field has focus, and the visible area shrank.
// Android's back button closes the keyboard but keeps the focus; a hardware keyboard shrinks nothing.
export const TYPING_FIELDS = [
    'textarea',
    '[contenteditable]:not([contenteditable="false"])',
    'input:not([type=checkbox]):not([type=radio]):not([type=range]):not([type=hidden])'
        + ':not([type=button]):not([type=submit]):not([type=reset]):not([type=color]):not([type=file])',
].join(', ');

// Smaller than this share of the tallest view seen at this width: the keyboard is up
const SHRUNK = 0.8;

export const watchKeyboard = (win = window) => {
    const doc = win.document;
    let tallest = { width: 0, height: 0 };

    const update = () => {
        const width = win.visualViewport?.width ?? win.innerWidth;
        const height = win.visualViewport?.height ?? win.innerHeight;

        // A new width (rotation) starts over; at the same width the tallest view is the keyboardless one
        if (Math.abs(width - tallest.width) > 1 || height > tallest.height) {
            tallest = { width, height };
        }

        const typing = Boolean(doc.activeElement?.matches?.(TYPING_FIELDS));
        doc.body.classList.toggle('keyboard-open', typing && height < tallest.height * SHRUNK);
    };

    // Deferred: moving from one field to the next blurs before it focuses, and the nav shouldn't flicker
    const later = () => win.setTimeout(update, 0);

    update();
    doc.addEventListener('focusin', update);
    doc.addEventListener('focusout', later);
    (win.visualViewport ?? win).addEventListener('resize', update);

    return () => {
        doc.removeEventListener('focusin', update);
        doc.removeEventListener('focusout', later);
        (win.visualViewport ?? win).removeEventListener('resize', update);
    };
};
