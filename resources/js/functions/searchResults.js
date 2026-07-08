/*
    ***
    Search result expansion and randomize button for Where2Fly
    ***
*/

// Expand the rows hidden behind the "Show more" fold
var expanded = false
function expandAllRows() {
    if(!expanded && document.querySelector('#showMoreRow')) {
        document.querySelectorAll('.showmore-hidden').forEach(function(element) {
            element.classList.remove('showmore-hidden');
        });
        document.querySelector('#showMoreRow').remove();
        expanded = true;
    }
}

// Exposed so the map can unfold the results when a below-the-fold airport is clicked
window.expandSearchResults = expandAllRows;

var showMoreBtn = document.querySelector('#showMoreBtn')
if(showMoreBtn){
    showMoreBtn.addEventListener('click', function() {
        expandAllRows();
    });

    // Expand all rows if user has clicked the table thead th's
    document.querySelectorAll('thead > tr > th').forEach(function(element) {
        element.addEventListener('click', function() {
            expandAllRows();
        });
    });
}

// Sync detail rows with parent rows after sorting
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('table[data-detail-table]').forEach(function(table) {
        function syncDetailRows() {
            var tbody = table.querySelector('tbody[data-detail-table-body]')

            if(!tbody) {
                return
            }

            tbody.querySelectorAll('tr[data-airport-icao]').forEach(function(parentRow) {
                var icao = parentRow.getAttribute('data-airport-icao')
                var detailRow = tbody.querySelector('tr[data-detail-row="' + icao + '"]')

                if(detailRow && parentRow.nextElementSibling !== detailRow) {
                    parentRow.insertAdjacentElement('afterend', detailRow)
                }
            })
        }

        table.addEventListener('sort-end', function() {
            syncDetailRows()
        })

        syncDetailRows()
    })
})

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