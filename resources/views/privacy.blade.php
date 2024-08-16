@extends('layouts.app')

@section('meta-description')
<meta name="description" content="We're only using strictly neccesarry service cookies to make the page and search work for your visit.">
@endsection

@section('title', 'Privacy Policy')
@section('content')

    @include('layouts.title', ['title' => 'Privacy Policy'])

    <div class="container">
        
        <div class="text-start">
            <p class="font-work-sans">Thank you for using Where2Fly. This is a hobby project, but I'm still striving my best to cover the requirements of GDPR and ePrivacy regulations. In short words, I'm only using strictly neccesarry service cookies to make the page and search work for your visit.</p>
            <a href="https://www.cloudflare.com/learning/privacy/what-are-cookies/" target="_blank">Read more about what a cookie is</a>

            <h2 class="mt-3">Session Cookies (where2fly_session, XSRF-token)</h2>
            <p class="font-work-sans">
                Sessions cookies are strictly required to perform and secure searches, and provide this service. This cookie is deleted when the browser window is closed. They do not collect or store any personal data.
            </p>
            
        </div>
    </div>
@endsection

@section('js')
    @vite('resources/js/map.js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            initMap();
        })
    </script>
@endsection