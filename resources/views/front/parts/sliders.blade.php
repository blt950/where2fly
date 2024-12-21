<script>
    // Run scripts when DOM is loaded
    document.addEventListener('DOMContentLoaded', function () {

        const userLocale = 'de-DE';

        // Sliders
        var temperatureSlider = document.getElementById('slider-temperature');
        noUiSlider.create(temperatureSlider, {
            start: [{{ old('temperatureMin') ? old('temperatureMin') : -60 }}, {{ old('temperatureMax') ? old('temperatureMax') : 60 }}],
            step: 2,
            connect: true,
            behaviour: 'drag',
            range: {
                'min': [-60],
                'max': [60]
            },
            handleAttributes: [
                { 'aria-label': 'lower' },
                { 'aria-label': 'upper' },
            ],
        });
        
        var temperatureSliderText = document.getElementById('slider-temperature-text');
        var temperatureMinInput = document.getElementById('temperatureMin');
        var temperatureMaxInput = document.getElementById('temperatureMax');
        temperatureSlider.noUiSlider.on('update', function (values) {
            temperatureSliderText.innerHTML = Math.round(values[0]) + ' to ' + Math.round(values[1]) + 'C';
            temperatureMinInput.value = Math.round(values[0])
            temperatureMaxInput.value = Math.round(values[1])
        });

        var elevationSlider = document.getElementById('slider-elevation');
        noUiSlider.create(elevationSlider, {
            start: [{{ old('elevationMin') ? old('elevationMin') : -2000 }}, {{ old('elevationMax') ? old('elevationMax') : 18000 }}],
            step: 1000,
            connect: true,
            behaviour: 'drag',
            range: {
                'min': [-2000],
                'max': [18000]
            },
            handleAttributes: [
                { 'aria-label': 'lower' },
                { 'aria-label': 'upper' },
            ],
        });
        
        var elevationSliderText = document.getElementById('slider-elevation-text');
        var elevationMinInput = document.getElementById('elevationMin');
        var elevationMaxInput = document.getElementById('elevationMax');
        elevationSlider.noUiSlider.on('update', function (values) {
            elevationSliderText.innerHTML = Math.round(values[0]).toLocaleString(userLocale) + '-' + Math.round(values[1]).toLocaleString(userLocale) + 'ft';
            elevationMinInput.value = Math.round(values[0])
            elevationMaxInput.value = Math.round(values[1])
        });
        
        var rwySlider = document.getElementById('slider-rwy');
        noUiSlider.create(rwySlider, {
            start: [{{ old('rwyLengthMin') ? old('rwyLengthMin') : 0 }}, {{ old('rwyLengthMax') ? old('rwyLengthMax') : 17000 }}],
            step: 500,
            connect: true,
            behaviour: 'drag',
            range: {
                'min': [0],
                'max': [17000]
            },
            handleAttributes: [
                { 'aria-label': 'lower' },
                { 'aria-label': 'upper' },
            ],
        });
        
        var rwySliderText = document.getElementById('slider-rwy-text');
        var rwyMinInput = document.getElementById('rwyLengthMin');
        var rwyMaxInput = document.getElementById('rwyLengthMax');
        rwySlider.noUiSlider.on('update', function (values) {
            rwySliderText.innerHTML = Math.round(values[0]).toLocaleString(userLocale) + '-' + Math.round(values[1]).toLocaleString(userLocale) + 'ft <span class="text-white text-opacity-75"> (' + Math.round(values[0]/3.2808).toLocaleString(userLocale) + '-' + Math.round(values[1]/3.2808).toLocaleString(userLocale) + 'm)</span>';
            rwyMinInput.value = Math.round(values[0])
            rwyMaxInput.value = Math.round(values[1])
        });
        
        var airtimeSlider = document.getElementById('slider-airtime');
        noUiSlider.create(airtimeSlider, {
            start: [{{ old('airtimeMin') ? old('airtimeMin') : 0 }}, {{ old('airtimeMax') ? old('airtimeMax') : 5 }}],
            step: 1,
            connect: true,
            behaviour: 'drag',
            range: {
                'min': [0],
                'max': [12]
            },
            handleAttributes: [
                { 'aria-label': 'lower' },
                { 'aria-label': 'upper' },
            ],
        });
        
        var airtimeSliderText = document.getElementById('slider-airtime-text');
        var airtimeMinInput = document.getElementById('airtimeMin');
        var airtimeMaxInput = document.getElementById('airtimeMax');
        airtimeSlider.noUiSlider.on('update', function (values) {

            if(values[1] == 12){
                airtimeSliderText.innerHTML = Math.round(values[0]) + '-' + Math.round(values[1]) + '+ hours';
            } else {
                airtimeSliderText.innerHTML = Math.round(values[0]) + '-' + Math.round(values[1]) + ' hours';
            }

            airtimeMinInput.value = Math.round(values[0])
            airtimeMaxInput.value = Math.round(values[1])
        });
    }, false);
</script>