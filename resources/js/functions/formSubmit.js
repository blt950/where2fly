// Forms marked with data-submit-once disable their submit buttons and show a
// spinner as soon as submission starts.
var spinner = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

document.querySelectorAll('form[data-submit-once]').forEach(function(form) {

    var buttons = Array.from(form.elements).filter(function(element) {
        return element.type === 'submit';
    });

    var states = buttons.map(function(button) {
        return { idle: button.innerHTML, busy: button.textContent.trim() + '&nbsp;&nbsp;' + spinner };
    });

    form.addEventListener('submit', function() {
        buttons.forEach(function(button, index) {
            button.setAttribute('disabled', '');
            button.innerHTML = states[index].busy;
        });
    });

    // Restore when the page is served from the bfcache, otherwise navigating
    // back leaves a permanently disabled button behind
    addEventListener('pageshow', function() {
        buttons.forEach(function(button, index) {
            button.removeAttribute('disabled');
            button.innerHTML = states[index].idle;
        });
    });
});
