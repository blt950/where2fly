<script>
    var airportMapData = {!! isset($airportMapData) ? $airportMapData : '[]' !!}

    document.addEventListener('DOMContentLoaded', function() {
        mapInit();

        var cluster = mapCreateCluster('inverted');
        mapDrawClickableAirports(airportMapData, cluster);
        map.addLayer(cluster);

        mapEventCardOpenPan(airportMapData);
        mapSaveView();
    })
</script>