import { Modal } from 'bootstrap'
import { init, sendFeedback, setUser } from '@sentry/browser'

const trigger = document.getElementById('report-bug')
const form = document.getElementById('bugReportForm')

if (trigger && form && trigger.dataset.dsn) {
    const modalEl = document.getElementById('bugReportModal')
    const modal = new Modal(modalEl)
    const field = document.getElementById('bugReportMessage')
    const submit = document.getElementById('bugReportSubmit')
    const submitLabel = submit.textContent
    const error = document.getElementById('bugReportError')
    const success = document.getElementById('bugReportSuccess')

    init({
        dsn: trigger.dataset.dsn,
        // Feedback only: no automatic browser error capture, which stays a backend concern.
        defaultIntegrations: false,
    })

    // Logged-in reporters are identifiable in Sentry without the form asking for anything.
    if (trigger.dataset.userId) {
        setUser({ id: trigger.dataset.userId })
    }

    trigger.addEventListener('click', () => modal.show())

    modalEl.addEventListener('shown.bs.modal', () => field.focus())

    // Back to a blank form, so reopening after a send does not show the previous result.
    modalEl.addEventListener('hidden.bs.modal', () => {
        form.reset()
        form.hidden = false
        success.hidden = true
        error.hidden = true
        field.removeAttribute('aria-invalid')
        field.setAttribute('aria-describedby', 'bugReportHelp')
    })

    function showError(message) {
        error.textContent = message
        error.hidden = false
        field.setAttribute('aria-invalid', 'true')
        field.setAttribute('aria-describedby', 'bugReportError bugReportHelp')
        field.focus()
    }

    form.addEventListener('submit', (event) => {
        event.preventDefault()

        const message = field.value.trim()

        if (!message) {
            showError('Please describe what went wrong.')
            return
        }

        error.hidden = true
        field.removeAttribute('aria-invalid')
        submit.disabled = true
        submit.textContent = 'Sending...'

        // sendFeedback resolves only once the transport reports a 2xx, so an ad blocker that
        // swallows the request surfaces here rather than being reported as success.
        sendFeedback({ message })
            .then(() => {
                form.hidden = true
                success.hidden = false
                // Focus would otherwise be stranded on the now-hidden submit button.
                success.focus()
            })
            .catch((reason) => {
                console.error('[w2f] feedback not sent:', reason)
                showError('We could not send that report. An ad blocker may be stopping it.')
            })
            .finally(() => {
                submit.disabled = false
                submit.textContent = submitLabel
            })
    })
}
