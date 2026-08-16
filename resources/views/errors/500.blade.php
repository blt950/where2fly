@extends('errors::minimal')

@section('title', 'Server Error')
@section('code', '500')
@section('message', 'Server Error')

@section('icon')
    @include('errors.icons.nosedive')
@endsection

{{-- Feedback is attached to the event the reportable() handler in bootstrap/app.php just captured,
     so the form is only worth showing when Sentry actually recorded one. --}}
@if (config('sentry.dsn') && app()->bound('sentry') && app('sentry')->getLastEventId())
    @section('feedback')
        <style>
            .w2f-feedback {
                margin-top: 2rem;
                padding: 1.25rem;
                background: var(--w2f-panel);
            }
            .w2f-feedback label {
                display: block;
                margin-bottom: .5rem;
                font-family: 'Kanit', 'Work Sans', sans-serif;
                font-size: .875rem;
            }
            .w2f-feedback textarea {
                display: block;
                width: 100%;
                padding: .625rem;
                background: var(--w2f-bg);
                color: var(--w2f-offwhite);
                border: 1px solid var(--w2f-muted);
                border-radius: 0;
                font: inherit;
                resize: vertical;
            }
            .w2f-feedback textarea:focus {
                outline: none;
                border-color: var(--w2f-primary);
            }
            .w2f-feedback textarea::placeholder {
                color: var(--w2f-muted);
            }
            .w2f-feedback button {
                margin-top: .75rem;
                padding: .5rem 1.25rem;
                background: var(--w2f-primary);
                color: var(--w2f-bg);
                border: 0;
                border-radius: 0;
                font: inherit;
                font-family: 'Kanit', 'Work Sans', sans-serif;
                cursor: pointer;
            }
            .w2f-feedback button:hover:not(:disabled) {
                background: var(--w2f-primary-hover);
            }
            .w2f-feedback button:disabled {
                opacity: .6;
                cursor: default;
            }
            .w2f-feedback-status {
                margin: 0;
                color: var(--w2f-primary);
            }
            .w2f-feedback-status.is-error {
                color: var(--w2f-error);
            }
        </style>

        {{-- Hidden until the SDK is confirmed loaded, so a blocked script never shows a dead form. --}}
        <form class="w2f-feedback" id="w2f-feedback" hidden>
            <div id="w2f-feedback-fields">
                <label for="w2f-feedback-message">Tell us what happened</label>
                <textarea
                    id="w2f-feedback-message"
                    name="message"
                    rows="4"
                    required
                    placeholder="e.g. I clicked X and then this error happened."
                ></textarea>
                <button type="submit">Send report</button>
            </div>
            <p class="w2f-feedback-status" role="status" aria-live="polite" hidden></p>
        </form>

        {{-- The feedback bundle, not the base one: only it ships sendFeedback(), which fills in the
             url/source fields and resolves against the real HTTP status instead of fire-and-forget. --}}
        <script
            src="https://browser.sentry-cdn.com/10.70.0/bundle.feedback.min.js"
            integrity="sha384-i/V864vT/71bOcxAzIf1EaSRevgRoWWXdzivP15Zp9bX+vsH0kbQ6NwUXcu3qleZ"
            crossorigin="anonymous"
        ></script>
        <script>
            (function () {
                // An ad blocker that eats the CDN script leaves nothing to submit to.
                if (typeof Sentry === 'undefined') {
                    return;
                }

                var dsn = @json(config('sentry.dsn'));

                Sentry.init({ dsn: dsn });

                var form = document.getElementById('w2f-feedback');
                var fields = document.getElementById('w2f-feedback-fields');
                var field = document.getElementById('w2f-feedback-message');
                var button = form.querySelector('button');
                var status = form.querySelector('.w2f-feedback-status');
                var submitLabel = button.textContent;

                // Brave and friends allow the SDK but block the ingest host, which would otherwise
                // only surface after someone has typed a report. no-cors rejects only on a real
                // network-level block, so reaching Sentry at all is enough to show the form.
                var parts = /^https:\/\/[^@]+@([^/]+)\/(\d+)\/?$/.exec(dsn);
                if (!parts) {
                    return;
                }

                fetch('https://' + parts[1] + '/api/' + parts[2] + '/envelope/', {
                    method: 'HEAD',
                    mode: 'no-cors',
                    cache: 'no-store',
                }).then(function () {
                    form.hidden = false;
                }).catch(function (error) {
                    console.warn('[w2f] sentry ingest unreachable, feedback form hidden:', error);
                });

                form.addEventListener('submit', function (event) {
                    event.preventDefault();

                    var message = field.value.trim();
                    if (!message) {
                        return;
                    }

                    button.disabled = true;
                    button.textContent = 'Sending...';

                    function fail(reason) {
                        console.error('[w2f] feedback not sent:', reason);
                        status.hidden = false;
                        status.classList.add('is-error');
                        status.textContent = 'We could not send that report. Please try again.';
                        button.disabled = false;
                        button.textContent = submitLabel;
                    }

                    try {
                        Sentry.sendFeedback({
                            message: message,
                            associatedEventId: @json((string) app('sentry')->getLastEventId()),
                        }).then(function () {
                            status.hidden = false;
                            fields.hidden = true;
                            status.textContent = 'Thank you for helping us!';
                        }).catch(fail);
                    } catch (error) {
                        fail(error);
                    }
                });
            })();
        </script>
    @endsection
@endif
