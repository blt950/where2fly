@extends('layouts.app')

@section('title', 'Privacy Policy')
@section('content')

<div class="cover-container d-flex w-100 h-100 p-3 mx-auto flex-column">

    @include('layouts.menu')
  
    <main>
        <h1 class="mb-3 mt-5 text-start">Privacy Policy</h1>
        
        <div class="text-start">
            <p>When using the websites of Posten Norge and Bring, you agree to allow us to place cookies in your browser.</p>
            <p>"Cookies" - or cookies - are small text files that are added to your browser's internal memory when you download a website. These are used, among other things, to improve user experience and content, provide us with data for analysis and statistics, personalization and marketing. The most commonly used browsers (Google Chrome, Firefox, Safari, Internet Explorer, Opera etc.) are set up to accept cookies automatically, but you can choose to change the settings yourself so that cookies are not accepted. You do this in the browser's help section and it will then delete all cookies and block new ones, or give notice before a cookie is stored.</p>

            <h2>Google Analytics</h2>
            <p>
                An analysis tool that records the user pattern on posten.no so we better understand how you use our website. The data is used to improve the site and your user experience. On Google's own pages, you can read more about how they collect and protect data.
            </p>
            <button id="consent-btn" class="btn btn-sm btn-danger" onclick="revokeCookieConsent()">Withdraw my cookie consent</button>
        </div>

        <script>

            var consentBtn = document.getElementById('consent-btn')
            function revokeCookieConsent(){
                consentBtn.innerHTML = 'Consent successfully withdrawn!'
                consentBtn.setAttribute('disabled', '')
                cookieConsent(false)
            }

            window.addEventListener('DOMContentLoaded', (event) => {
                
                if(consent == 'true'){
                    consentBtn.innerHTML = 'Withdraw my cookie consent'
                } else {
                    consentBtn.remove()
                }
            })
        </script>

    </main>
  
    @include('layouts.footer')
</div>

@endsection