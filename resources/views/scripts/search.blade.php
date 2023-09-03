<script>
    var button = document.getElementById('submitBtn');
    button.addEventListener('click', function() {
        button.setAttribute('disabled', '')
        button.innerHTML = 'Search <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>'
        document.getElementById('form').submit()
    });

    addEventListener('pageshow', (event) => {
        button.removeAttribute('disabled')
        button.innerHTML = 'Search <i class="fas fa-search"></i>'
    });
</script>