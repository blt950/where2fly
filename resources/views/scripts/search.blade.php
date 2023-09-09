<script>

    //
    // Search
    //

    var button = document.getElementById('submitBtn');
    button.addEventListener('click', function() {
        button.setAttribute('disabled', '')
        button.innerHTML = 'Search&nbsp;&nbsp;<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>'
        document.getElementById('form').submit()
    });

    addEventListener('pageshow', (event) => {
        button.removeAttribute('disabled')
        button.innerHTML = 'Search&nbsp;&nbsp;<i class="fas fa-search"></i>'

        // Expand filters if they were expanded prior to page navigation
        if(sessionStorage.getItem('filterExpanded') == 'true'){
            expandFilters()
        }
    });

    //
    // Filters
    //

    var filterExpanded = false;
    function toggleFilters(){
        if (!filterExpanded) {
            expandFilters();
        } else {
            contractFilters();
        }
    }

    function contractFilters(){
        var filter = document.getElementById('filters');

        filter.classList.add('hide-filters');
        document.getElementById('expandFilters').innerHTML = 'Show more filters';
        sessionStorage.setItem('filterExpanded', false);

        filterExpanded = false;
    }

    function expandFilters(filter){
        var filter = document.getElementById('filters');

        filter.classList.remove('hide-filters');
        document.getElementById('expandFilters').innerHTML = 'Hide filters';
        sessionStorage.setItem('filterExpanded', true);

        filterExpanded = true;
    }

    document.getElementById('expandFilters').addEventListener('click', function () {
        toggleFilters()
    });

</script>