import { useEffect, useRef, useState } from 'react';
import { sendFeedback } from '@sentry/react';

// The bug report button and modal. Sends through the app's own Sentry client (sentry.js), which
// already knows the user: re-initialising Sentry here would replace it for the rest of the visit.
const BugReport = () => {
    const modalRef = useRef(null);
    const fieldRef = useRef(null);
    const [message, setMessage] = useState('');
    const [error, setError] = useState(null);
    const [sending, setSending] = useState(false);
    const [sent, setSent] = useState(false);

    useEffect(() => {
        const element = modalRef.current;
        const modal = bootstrap.Modal.getOrCreateInstance(element);
        const focus = () => fieldRef.current?.focus();
        // Back to a blank form, so reopening after a send doesn't show the previous result
        const reset = () => { setMessage(''); setError(null); setSent(false); };

        element.addEventListener('shown.bs.modal', focus);
        element.addEventListener('hidden.bs.modal', reset);

        return () => {
            element.removeEventListener('shown.bs.modal', focus);
            element.removeEventListener('hidden.bs.modal', reset);
            modal.dispose();
        };
    }, []);

    const submit = (event) => {
        event.preventDefault();

        if (!message.trim()) {
            setError('Please describe what went wrong.');
            fieldRef.current?.focus();
            return;
        }

        setError(null);
        setSending(true);

        // Resolves only once the transport reports a 2xx, so an ad blocker that swallows the
        // request surfaces here rather than being reported as success
        sendFeedback({ message: message.trim() })
            .then(() => setSent(true))
            .catch((reason) => {
                console.error('[w2f] feedback not sent:', reason);
                setError('We could not send that report. An ad blocker may be stopping it.');
            })
            .finally(() => setSending(false));
    };

    return (
        <>
            <button type="button" className="btn btn-outline-primary w-100 mb-2" onClick={() => bootstrap.Modal.getOrCreateInstance(modalRef.current).show()}>
                Report a bug <i className="fa-sharp fa-bug"></i>
            </button>

            <div ref={modalRef} className="modal fade bug-report" id="bugReportModal" tabIndex="-1" aria-labelledby="bugReportModalLabel" aria-hidden="true">
                <div className="modal-dialog modal-dialog-centered">
                    <div className="modal-content">
                        <div className="modal-header">
                            <h3 id="bugReportModalLabel">Report a bug</h3>
                            <button type="button" className="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        {sent ? (
                            <div role="status">
                                <div className="modal-body bg-success">
                                    <p className="text-white mb-0">
                                        <i className="fa-sharp fa-circle-check" aria-hidden="true"></i> Thank you for reporting! We read all bug reports, but we&apos;re not able to reply you directly.
                                    </p>
                                </div>
                                <div className="modal-footer">
                                    <button type="button" className="btn btn-light" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        ) : (
                            <form onSubmit={submit} noValidate>
                                <div className="modal-body">
                                    {error && <p className="validation-error mb-3" id="bugReportError" role="alert">{error}</p>}

                                    <label htmlFor="bugReportMessage" className="form-label mb-1">What went wrong?</label>
                                    <textarea ref={fieldRef} className="form-control" id="bugReportMessage" rows="7" required
                                        value={message} onChange={(e) => setMessage(e.target.value)}
                                        aria-invalid={error ? 'true' : undefined}
                                        aria-describedby={error ? 'bugReportError bugReportHelp' : 'bugReportHelp'}
                                        placeholder="Explain the issue and how to it reproduce in detail. Please also provide your browser and version."></textarea>

                                    <p className="form-text mt-2 mb-0" id="bugReportHelp">
                                        Please describe your issue thoroughly as we&apos;re not able to reply to your bug report.
                                    </p>
                                </div>

                                <div className="modal-footer">
                                    <button type="button" className="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" className="btn btn-primary" disabled={sending}>{sending ? 'Sending...' : 'Send report'}</button>
                                </div>
                            </form>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
};

export default BugReport;
