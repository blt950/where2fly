<!-- Leaflet -->

<aside>
    <div id="map" class="map"></div>
</aside>

@vite('resources/js/leaflet.js')
<script>

    airportCoordinates = {!! isset($airportCoordinates) ? json_encode($airportCoordinates) : '[]' !!}
    primaryAirport = {!! isset($primaryAirport) ? '\''.$primaryAirport->icao.'\'' : 'null' !!};
    var continentFilter = {!! isset($continent) ? '\''.$continent.'\'' : 'null' !!}

    document.addEventListener('DOMContentLoaded', function () {

        var lat = 52.51843039016386;
        var lon = 13.395199187248908;

        if(primaryAirport !== null && airportCoordinates !== undefined && Object.keys(airportCoordinates).length > 0){
            lat = airportCoordinates[primaryAirport]['lat'];
            lon = airportCoordinates[primaryAirport]['lon'];
        }

        if(continentFilter !== undefined && continentFilter !== null){
            if(continentFilter == 'AF'){
                lat = 7.1881;
                lon = 21.0936;
            } else if(continentFilter == 'AS'){
                lat = 34.0479;
                lon = 100.6197;
            } else if(continentFilter == 'EU'){
                lat = 54.5260;
                lon = 15.2551;
            } else if(continentFilter == 'NA'){
                lat = 37.0902;
                lon = -95.7129;
            } else if(continentFilter == 'OC'){
                lat = -25.2744;
                lon = 133.7751;
            } else if(continentFilter == 'SA'){
                lat = -8.7832;
                lon = -55.4915;
            }
        }

        map = L.map('map', {
            attributionControl: false,
            zoomControl: false,
        }).setView([lat, lon], 4);

        map.setMaxBounds([
            [-85, -250], // Southwest corner of the bounds
            [85, 250]    // Northeast corner of the bounds
        ]);

        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_nolabels/{z}/{x}/{y}{r}.png', {
            minZoom: 3,
            maxZoom: 17,
        }).addTo(map);
    });
</script>