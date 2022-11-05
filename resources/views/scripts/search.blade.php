<script>
    var button = document.getElementById('submitBtn');
    button.addEventListener('click', function() {
        button.setAttribute('disabled', '')
        button.innerHTML = 'Searching ... <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>'
        document.getElementById('form').submit()
    });

    addEventListener('pageshow', (event) => {
        button.removeAttribute('disabled')
        button.innerHTML = "Find destination"
    });
</script>