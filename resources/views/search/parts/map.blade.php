<!-- Leaflet -->
<div id="map" class="map"></div>
@vite('resources/js/leaflet.js')

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var map = L.map('map', {
            attributionControl: false,
            zoomControl: false
        }).setView([51.505, -0.09], 5);

        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_nolabels/{z}/{x}/{y}{r}.png', {
            minZoom: 2,
            maxZoom: 17,
            attribution: '&copy; <a href="https://cartodb.com/attribution">CartoDB</a>'
        }).addTo(map);

        // L.control.zoom({
        //     position: 'bottomleft'
        // }).addTo(map);
    });
</script>