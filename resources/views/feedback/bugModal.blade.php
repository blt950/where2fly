<div class="modal fade bug-report" id="bugReportModal" tabindex="-1" aria-labelledby="bugReportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="bugReportModalLabel">Report a bug</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="bugReportForm" novalidate>
                <div class="modal-body">
                    <p class="validation-error mb-3" id="bugReportError" role="alert" hidden></p>

                    <label for="bugReportMessage" class="form-label mb-1">What went wrong?</label>

                    <textarea class="form-control" id="bugReportMessage" name="message" rows="7" required
                              aria-describedby="bugReportHelp"
                              placeholder="Explain the issue and how to it reproduce in detail. Please also provide your browser and version."></textarea>

                    <p class="form-text mt-0 mt-2 mb-0">
                        Please describe your issue thoroughly as we're not able to reply to your bug report.
                    </p>
                    
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="bugReportSubmit">Send report</button>
                </div>
            </form>

            <div id="bugReportSuccess" role="status" tabindex="-1" hidden>
                <div class="modal-body bg-success">
                    <p class="text-white mb-0">
                        <i class="fa-sharp fa-circle-check" aria-hidden="true"></i> Thank you for reporting! We read all bug reports, but we're not able to reply you directly.
                    </p>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
