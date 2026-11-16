import { useEffect, useRef } from 'react';

import { reportError } from '../utils/http';

const SCRIPT = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
let loading = null;

const loadTurnstile = () => {
    if (window.turnstile) {
        return Promise.resolve(window.turnstile);
    }

    loading ??= new Promise((resolve, reject) => {
        const script = Object.assign(document.createElement('script'), { src: SCRIPT, async: true });
        script.onload = () => resolve(window.turnstile);
        script.onerror = () => {
            loading = null;
            script.remove();
            reject(new Error('Turnstile failed to load'));
        };
        document.head.append(script);
    });

    return loading;
};

// Cloudflare's challenge, rendered explicitly so React owns its lifetime. The token goes to `onToken`
// (empty when it expires); a new `resetKey` (after a failed submit: each token is single-use) asks for another.
const Turnstile = ({ siteKey, action, onToken, resetKey = 0 }) => {
    const box = useRef(null);
    const widget = useRef(null);
    const latestOnToken = useRef(onToken);
    latestOnToken.current = onToken;

    useEffect(() => {
        let live = true;

        loadTurnstile().then((turnstile) => {
            if (!live || !box.current) return;

            const send = (token) => latestOnToken.current(token);
            widget.current = turnstile.render(box.current, {
                sitekey: siteKey,
                action,
                theme: 'light',
                language: 'en',
                callback: send,
                'expired-callback': () => send(''),
                'error-callback': () => send(''),
            });
        }).catch(reportError);

        return () => {
            live = false;
            if (widget.current !== null) {
                window.turnstile?.remove(widget.current);
                widget.current = null;
            }
        };
    }, [siteKey, action]);

    useEffect(() => {
        if (resetKey && widget.current !== null) {
            window.turnstile?.reset(widget.current);
            latestOnToken.current('');
        }
    }, [resetKey]);

    return <div ref={box} className="cf-turnstile mb-2"></div>;
};

export default Turnstile;
