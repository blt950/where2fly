<script>
    var routePath = null;
    function drawRoute(primaryAirport, destinationAirport){
        var latlngs = [];
        var latlng1 = [];
        var latlng2 = [];

        if('{{ isset($direction) ? $direction : 'null' }}' == 'arrival'){
            latlng1 = [airportCoordinates[destinationAirport]['lat'], airportCoordinates[destinationAirport]['lon']];
            latlng2 = [airportCoordinates[primaryAirport]['lat'], airportCoordinates[primaryAirport]['lon']];
        } else {
            latlng1 = [airportCoordinates[primaryAirport]['lat'], airportCoordinates[primaryAirport]['lon']];
            latlng2 = [airportCoordinates[destinationAirport]['lat'], airportCoordinates[destinationAirport]['lon']];
        }

        var offsetX = latlng2[1] - latlng1[1],
            offsetY = latlng2[0] - latlng1[0];

        var r = Math.sqrt( Math.pow(offsetX, 2) + Math.pow(offsetY, 2) ),
            theta = Math.atan2(offsetY, offsetX);

        var thetaOffset = (3.14/10);

        var r2 = (r/2)/(Math.cos(thetaOffset)),
            theta2 = theta + thetaOffset;

        var midpointX = (r2 * Math.cos(theta2)) + latlng1[1],
            midpointY = (r2 * Math.sin(theta2)) + latlng1[0];

        var midpointLatLng = [midpointY, midpointX];

        latlngs.push(latlng1, midpointLatLng, latlng2);

        var pathOptions = {
            color: 'rgba(208, 198, 5, 1)',
            weight: 2,
            renderer: L.svg()
        }

        var durationBase = 200;
        var duration = Math.sqrt(Math.log(r)) * durationBase;

        pathOptions.animate = {
            duration: duration,
            iterations: 1,
            easing: 'ease-in-out',
            direction: 'alternate'
        }

        if(routePath) {
            routePath.remove();
        }

        routePath = L.curve(
            [
                'M', latlng1,
                'Q', midpointLatLng,
                    latlng2
            ], pathOptions)

        map.flyToBounds(routePath.getBounds(), {duration: 0.35, minZoom: 3, maxZoom: 7, paddingTopLeft: [400, 350], paddingBottomRight: [75, 50]});

        drawLabel(primaryAirport, true);
        drawLabel(destinationAirport);

        setTimeout(() => {
            routePath.addTo(map);
        }, 350);

        // Redraw as canvas renderer to fish the line becoming dashes
        setTimeout(() => {

            routePath.remove();

            pathOptions.renderer = L.canvas();
            pathOptions.animate = [];

            routePath = L.curve(
                [
                    'M', latlng1,
                    'Q', midpointLatLng,
                        latlng2
                ], pathOptions).addTo(map);
        }, 350 + duration);
        
    }

    var primary = null;
    var suggestion = null;
    function drawLabel(airport, isPrimary = false){

        if(primary && isPrimary) { return }
        if(suggestion) { suggestion.remove() }

        var stepIcon = L.icon({
            iconUrl: '{{ asset('img/circle.svg') }}',
            iconSize: [12, 12],
        });

        var marker = new L.marker([airportCoordinates[airport]['lat'], airportCoordinates[airport]['lon']], { icon:stepIcon});
        marker.bindTooltip(airport, {permanent: true, direction: 'left', className: "airport"});
        marker.addTo(map);

        if(isPrimary){
            primary = marker;
        } else {
            suggestion = marker;
        }
    }
</script>