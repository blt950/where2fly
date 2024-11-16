/*
    ***
    Fetch TAF for Where2Fly
    ***
*/

var tafButtons = document.querySelectorAll('[data-taf-button="true"]');
tafButtons.forEach(element => {
    element.addEventListener('click', function() {
        fetchTAF(element.getAttribute('data-airport-icao'), element)
    });
})

function fetchTAF(icao, element){

    plausible('Interactions', {props: {interaction: 'Fetch TAF'}});
    umami.track('Interactions', {interaction: 'Fetch TAF'});

    fetch('https://api.met.no/weatherapi/tafmetar/1.0/taf.txt?icao='+icao)
        .then(response => {
            if (!response.ok) {
                throw new Error("HTTP error " + response.status);
            }
            return response.text()
        })
        .then(text => {
            if(text == ""){
                element.outerHTML = 'Not Available'
            } else {
                var lines = text.match(/[^\r\n]+/g)
                element.outerHTML = lines[lines.length -1]
            }
        })
        .catch(error => {
            element.outerHTML = 'TAF Fetch failed'
        });

}