<script>
    var airportMapData = {!! isset($airportMapData) ? $airportMapData : '[]' !!}

    /*document.addEventListener('DOMContentLoaded', function() {
        cardsInitEvents()
        mapInit();

        var cluster = mapCreateCluster('inverted');
        mapDrawClickableAirports(airportMapData, cluster);
        map.addLayer(cluster);

        mapEventCardOpenPan(airportMapData);
        mapSaveView();

        document.addEventListener('cardOpened', function(event) {
            if(event.detail.type == 'airport'){
                cardCloseAll('scenery')
            }
        })
    })*/
</script>