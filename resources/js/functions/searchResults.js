/*
    ***
    Search result expansion and randomize button for Where2Fly
    ***
*/

var showMoreBtn = document.querySelector('#showMoreBtn')
if(showMoreBtn){
    document.querySelector('#showMoreBtn').addEventListener('click', function() {
        expandAllRows();
    });

    // Expand all rows if user has clicked the table thead th's
    document.querySelectorAll('thead > tr > th').forEach(function(element) {
        element.addEventListener('click', function() {
            expandAllRows();
        });
    });

    // Function to expand all rows
    var expanded = false
    function expandAllRows() {
        if(!expanded) {
            document.querySelectorAll('.showmore-hidden').forEach(function(element) {
                element.classList.remove('showmore-hidden');
            });
            document.querySelector('#showMoreRow').remove();
            expanded = true;
        }
    }
}

// Randomise spinner
var button = document.getElementById('randomiseBtn');
var form = document.getElementById('randomiseForm');
if(button){
    button.addEventListener('click', function() {
        button.setAttribute('disabled', '')
        button.innerHTML = 'Randomise&nbsp;&nbsp;<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>'
        form.requestSubmit()
    });
}