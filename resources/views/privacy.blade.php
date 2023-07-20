@extends('layouts.app')

@section('title', 'Privacy Policy')
@section('content')

<div class="cover-container d-flex w-100 h-100 p-3 mx-auto flex-column">

    @include('layouts.menu')
  
    <main>
        <h1 class="mb-3 mt-5 text-start">Privacy Policy</h1>
        
        <div class="text-start">
            <p class="font-work-sans">Thank you for using Where2Fly. This is a hobby project, but I'm still striving my best to cover the requirements of GDPR and ePrivacy regulations. In short words, I'm only using strictly neccesarry service cookies to make the page and search work for your visit.</p>
            <a href="https://www.cloudflare.com/learning/privacy/what-are-cookies/" target="_blank">Read more about what a cookie is</a>

            <h2 class="mt-3">Session Cookies (where2fly_session, XSRF-token)</h2>
            <p class="font-work-sans">
                Sessions cookies are strictly required to perform and secure searches, and provide this service. This cookie is deleted when the browser window is closed. They do not collect or store any personal data.
            </p>
            
        </div>

    </main>
  
    @include('layouts.footer')
</div>

@endsection