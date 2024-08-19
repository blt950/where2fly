@extends('layouts.app')

@section('meta-description')
<meta name="description" content="We're only using strictly neccesarry service cookies to make the page and search work for your visit.">
@endsection

@section('title', 'Privacy Policy')
@section('content')

    @include('layouts.title', ['title' => 'Privacy Policy'])

    <div class="container">
        
        <div class="text-start">
            <p class="font-work-sans">Thank you for using Where2Fly. Even though this is only a hobby project, I strive to meet the privacy requirements of GDPR and ePrivacy regulations.</p>
            <p class="font-work-sans">In short, I aim to collect as little data about you as possible. I minimize the use of cookies, choose privacy-friendly services, and carefully balance what data is collected.</p>
            
            <h2 class="mt-4 pt-4 border-top">For all visitors</h2>
            
            <h3 class="fs-5 fw-normal">Session Cookies</h3>
            <p class="font-work-sans">"where2fly_session" and "XSRF-token" are session cookies strictly required to perform and secure searches, and provide this service. They are deleted when the browser window is closed and do not collect or store personal data.</p>
        
            <h3 class="fs-5 fw-normal mt-3">Browser data</h3>
            <p class="font-work-sans">Automatically recorded by our servers for security, optimization, and functionality purposes. This may include your IP address, browser type, and language.</p>
            
            <h3 class="fs-5 fw-normal mt-3">Subprocessors</h3>
            <p class="font-work-sans">
            <ul>
                <li><u>Mailtrap:</u> Used as a subprocessor for sending out emails, ensuring that your email address is handled securely.</li>
                <li><u>Self-hosted Plausible Analytics:</u> Used for website analytics with privacy in mind, without tracking your personal data.</li>
            </ul>
        
            <h2 class="mt-4 pt-4 border-top">For Where2Fly account holders</h2>
            <p class="font-work-sans">When you register an account, the following data is collected and used:</p>
            <ul>
                <li><u>E-mail address:</u> Used to verify your account, recover your account, notify you of security breaches, and send important service-related updates (excluding marketing).</li>
                <li><u>Username:</u> Used for login and visible to other users as your identifier.</li>
                <li><u>Password:</u> Stored securely and hashed in the database.</li>
            </ul>
            <p class="font-work-sans"><strong>Legal Basis for Processing:</strong> Your data is processed based on your consent when creating an account and our legitimate interest in providing a secure, functional service.</p>
            <p class="font-work-sans"><strong>Data Retention:</strong> Your data is stored only as long as necessary to provide the service or as required by law. Account data is retained until you choose to delete your account.</p>
            
            <p class="font-work-sans"><strong>Your Rights:</strong> You have the right to access, rectify, or erase your personal data, restrict or object to its processing, and request data portability. To exercise these rights, please contact Blt950 on Discord.</p>
            <p class="font-work-sans"><strong>Contact Us:</strong> For any questions or concerns about this privacy policy, please contact Blt950 on Discord.</p>
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