<script>

    var tafButtons = document.querySelectorAll('[data-taf-button="true"]');
    tafButtons.forEach(element => {
        element.addEventListener('click', function() {
            fetchTAF(element.getAttribute('data-airport-icao'), element.getAttribute('data-bs-target'))
        });
    })
    
    function fetchTAF(icao, pane){

        paneElement = document.querySelector(pane)

        fetch('https://api.met.no/weatherapi/tafmetar/1.0/taf.txt?icao='+icao)
            .then(response => {
                if (!response.ok) {
                    throw new Error("HTTP error " + response.status);
                }
                return response.text()
            })
            .then(text => {
                if(text == ""){
                    paneElement.innerHTML = 'Not Available'
                } else {
                    var lines = text.match(/[^\r\n]+/g)
                    paneElement.innerHTML = lines[lines.length -1]
                }
            })
            .catch(error => {
                paneElement.innerHTML = 'TAF Fetch failed'
            });

    }

</script>