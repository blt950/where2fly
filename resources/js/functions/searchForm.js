/*
    ***
    Front submit and filter functionality for Where2Fly
    ***
*/

//
// Search
//

function submitFormMetrics(){
    var form = document.getElementById('form');
    var includeCheckboxes = [
        'scores[METAR_WINDY]',
        'scores[METAR_GUSTS]',
        'scores[METAR_CROSSWIND]',
        'scores[METAR_SIGHT]',
        'scores[METAR_RVR]',
        'scores[METAR_CEILING]',
        'scores[METAR_FOGGY]',
        'scores[METAR_HEAVY_RAIN]',
        'scores[METAR_HEAVY_SNOW]',
        'scores[METAR_THUNDERSTORM]',
        'metcondition',
        'scores[VATSIM_ATC]',
        'scores[VATSIM_EVENT]',
        'scores[VATSIM_POPULAR]',
        'destinationWithRoutesOnly',
        'destinationRunwayLights',
        'destinationAirbases',
        'flightDirection'
    ]
    var props = {}
    Array.from(form.elements).forEach(function(element){
        if(element.name == 'icao'){
            props.icao = element.value || 'Random';
        }

        if(element.name == 'continent'){
            var selected = element.options[element.selectedIndex];
            props.continent = selected.value;
        }

        if(element.name == 'codeletter'){
            var selected = element.options[element.selectedIndex];
            props.codeletter = selected.value;
        }

        if(element.name == 'sortByWeather'){
            props.sortByWeather = (element.checked) ? true : false;
        }

        if(element.name == 'sortByATC'){
            props.sortByATC = (element.checked) ? true : false;
        }

        includeCheckboxes.forEach(function(checkbox){
            if(element.name == checkbox & element.checked){
                // Remove the scores in name
                if(checkbox.includes('scores[')){
                    checkbox = checkbox.replace('scores[', '');
                    checkbox = checkbox.replace(']', '');
                }
                props[checkbox] = element.value;
            }
        });
    });

    plausible('Search', {props: props});
}

var submitButtons = Array.from(document.getElementsByClassName('submitBtn'));
submitButtons.forEach(function(button) {
    button.addEventListener('click', function() {
        var btns = Array.from(document.getElementsByClassName('submitBtn'));
        btns.forEach(function(btn) {
            btn.setAttribute('disabled', '')
            btn.innerHTML = 'Search&nbsp;&nbsp;<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>'
        });

        // Only submit if we're on / or /departures url, this way route search is not submitted
        if(window.location.pathname === '/' || window.location.pathname === '/departures'){
            submitFormMetrics();
        }

        document.getElementById('form').submit()
    });
});

addEventListener('pageshow', (event) => {

    submitButtons.forEach(function(button) {
        button.removeAttribute('disabled')
        button.innerHTML = 'Search&nbsp;&nbsp;<i class="fas fa-search"></i>'
    });

    // Expand filters if they were expanded prior to page navigation
    if(sessionStorage.getItem('filterExpanded') == 'true'){
        expandFilters(true)
    }
});

//
// Filters
//

var filterExpanded = false;
function toggleFilters(goingBack = false){
    if (!filterExpanded) {

        if(!goingBack){
            plausible('Interactions', {props: {interaction: 'Expand Filters'}});
        }

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

if(document.getElementById('expandFilters') !== null){
    document.getElementById('expandFilters').addEventListener('click', function () {
        toggleFilters()
    });
}