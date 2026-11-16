import { useState } from 'react';
import { getClient, sendFeedback } from '@sentry/react';

// "Tell us what happened" under a 500, attached to the crash's own Sentry event. Sends through the
// app's Sentry client (sentry.js), as BugReport does; without one (no DSN) there is nowhere to send.
const CrashFeedback = ({ eventId }) => {
    const [message, setMessage] = useState('');
    const [status, setStatus] = useState('idle');

    if (!eventId || !getClient()) {
        return null;
    }

    if (status === 'sent') {
        return <p className="crash-feedback text-success" role="status">Thank you for helping us!</p>;
    }

    const submit = (event) => {
        event.preventDefault();

        if (!message.trim()) return;

        setStatus('sending');
        sendFeedback({ message: message.trim(), associatedEventId: eventId })
            .then(() => setStatus('sent'))
            .catch((reason) => {
                console.error('[w2f] feedback not sent:', reason);
                setStatus('failed');
            });
    };

    return (
        <form className="crash-feedback mt-4" onSubmit={submit}>
            <label htmlFor="crash-feedback-message" className="form-label">Tell us what happened</label>
            <textarea id="crash-feedback-message" className="form-control" rows="4" required
                placeholder="e.g. I clicked X and then this error happened."
                value={message} onChange={(e) => setMessage(e.target.value)} />
            {status === 'failed' && (
                <p className="validation-error mt-2" role="alert">We could not send that report. An ad blocker may be stopping it; please try again.</p>
            )}
            <button type="submit" className="btn btn-primary mt-2" disabled={status === 'sending'}>
                {status === 'sending' ? 'Sending...' : 'Send report'}
            </button>
        </form>
    );
};

export default CrashFeedback;
