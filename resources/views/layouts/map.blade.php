<!-- Leaflet -->
<div id="map" class="map"></div>

@vite('resources/js/leaflet.js')
<script>

    airportCoordinates = {!! isset($airportCoordinates) ? json_encode($airportCoordinates) : '[]' !!}
    primaryAirport = {!! isset($primaryAirport) ? '\''.$primaryAirport->icao.'\'' : 'null' !!};

    document.addEventListener('DOMContentLoaded', function () {

        var lat = 52.51843039016386;
        var lon = 13.395199187248908;

        if(primaryAirport !== null && airportCoordinates !== undefined && Object.keys(airportCoordinates).length > 0){
            lat = airportCoordinates[primaryAirport]['lat'];
            lon = airportCoordinates[primaryAirport]['lon'];
        }

        map = L.map('map', {
            attributionControl: false,
            zoomControl: false,
        }).setView([lat, lon], 5);

        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_nolabels/{z}/{x}/{y}{r}.png', {
            minZoom: 2,
            maxZoom: 17,
        }).addTo(map);
    });
</script>