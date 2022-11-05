<div id="cookie-consent"></div>
<script>

    const consent = localStorage.getItem("cookiesAccepted")
    const banner = document.getElementById("cookie-consent")

    function cookieConsent(consent){
        if(consent){
            localStorage.setItem('cookiesAccepted', true);
            addAnalytics()
        } else {
            localStorage.setItem('cookiesAccepted', false);
        }

        banner.remove()
    }

    function addAnalytics(){
        var addGoogleAnalytics = document.createElement("script")
        addGoogleAnalytics.setAttribute("src","https://www.googletagmanager.com/gtag/js?id={{ Config::get('app.gtag') }}")
        addGoogleAnalytics.async = "true"
        document.head.appendChild(addGoogleAnalytics)

        var addDataLayer = document.createElement("script")
        var dataLayerData = document.createTextNode("window.dataLayer = window.dataLayer || []; \n function gtag(){dataLayer.push(arguments);} \n gtag('js', new Date()); \n gtag('config', '{{ Config::get('app.gtag') }}');")
        addDataLayer.appendChild(dataLayerData)
        document.head.appendChild(addDataLayer)
    }

    if(consent == 'true') {
        banner.remove()
        addAnalytics()
    } else if(consent == 'false') {
        banner.remove()
    } else {
        banner.innerHTML = `
            <p>Can I use cookies for statistics? I use them to improve the service <i class="fas fa-smile"></i></p>
            <a href="{{ route('privacy') }}">Show more details</a>
            <br><br>
            <button class="btn btn-sm btn-seconary text-white-50" onclick="cookieConsent(false)">Decline</button>
            <button class="btn btn-sm btn-primary" onclick="cookieConsent(true)">Accept</button>
        `;
    }

</script>