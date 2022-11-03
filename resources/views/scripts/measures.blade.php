<script>

    const userLocale = 'de-DE';
    const feets = document.querySelectorAll('.rwy-feet');
    const meters = document.querySelectorAll('.rwy-meters');
    feets.forEach(element => {
        element.innerHTML = parseInt(element.outerText).toLocaleString(userLocale) + 'ft'
    })
    meters.forEach(element => {
        element.innerHTML = parseInt(element.outerText).toLocaleString(userLocale) + 'm'
    })

</script>