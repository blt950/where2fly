<!-- Leaflet -->
<div id="map" class="map"></div>

@vite('resources/js/leaflet.js')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        map = L.map('map', {
            attributionControl: false,
            zoomControl: false,
        }).setView([52.51843039016386, 13.395199187248908], 5);

        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_nolabels/{z}/{x}/{y}{r}.png', {
            minZoom: 2,
            maxZoom: 17,
        }).addTo(map);
    });
</script>