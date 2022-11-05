@extends('layouts.app')

@section('title', 'Privacy Policy')
@section('content')

<div class="cover-container d-flex w-100 h-100 p-3 mx-auto flex-column">

    @include('layouts.menu')
  
    <main>
        <h1 class="mb-3 mt-5 text-start">Privacy Policy</h1>
        
        <div class="text-start">
            <p class="font-work-sans">Thank you for using Where2Fly. This is a hobby project, but I'm still striving my best to cover the requirements of GDPR and ePrivacy regulations. In short words, the only tracking cookie is the analytics one, which helps me understand how the page is used. I use this data to optimize the service and prioritze functionalities based on usage.</p>
            <a href="https://www.cloudflare.com/learning/privacy/what-are-cookies/" target="_blank">Read more about what a cookie is</a>

            <h2 class="mt-3">Session Cookies (where2fly_session, XSRF-token)</h2>
            <p class="font-work-sans">
                Sessions cookies are strictly required to perform and secure searches, and provide this service. This cookie is deleted when the browser window is closed. They do not collect or store any personal data.
            </p>

            <h2 class="mt-3">Google Analytics (_ga)</h2>
            <p class="font-work-sans">
                An analysis tool that records the user pattern on this site so we better understand how you use our website. The data is used to improve the site and your user experience. On Google's own pages, you can read more about how they collect and protect data.
            </p>

            <p id="consent-txt" class="mb-3 text-info"></p>
            <button id="consent-btn" class="btn btn-sm btn-danger" onclick="revokeCookieConsent()"></button>
            
        </div>

        <script>

            var consentBtn = document.getElementById('consent-btn')
            var consentTxt = document.getElementById('consent-txt')
            function revokeCookieConsent(){
                consentBtn.innerHTML = 'Cookies consent successfully reset!'
                consentBtn.setAttribute('disabled', '')
                localStorage.removeItem('cookiesAccepted');
            }

            window.addEventListener('DOMContentLoaded', (event) => {
                
                if(consent == 'true'){
                    consentTxt.innerHTML = '<i class="fas fa-circle-check"></i> You have accepted analytic cookies'
                    consentBtn.innerHTML = 'Withdraw and reset cookie consent'
                } else if(consent == 'false'){
                    consentTxt.innerHTML = '<i class="fas fa-circle-xmark"></i> You have declined analytic cookies'
                    consentBtn.innerHTML = 'Reset consent and ask me again'
                } else {
                    consentBtn.remove()
                }
            })
        </script>

    </main>
  
    @include('layouts.footer')
</div>

@endsection