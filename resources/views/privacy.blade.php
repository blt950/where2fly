@extends('layouts.app')

@section('meta-description')
<meta name="description" content="We're only using strictly neccesarry service cookies to make the page and search work for your visit.">
@endsection

@section('title', 'Privacy Policy')
@section('content')

    @include('layouts.title', ['title' => 'Privacy Policy'])

    <div class="container">
        
        <div class="text-start">
            <p>Thank you for using Where2Fly. Even though this is only a hobby project, I strive to meet the privacy requirements of GDPR and ePrivacy regulations.</p>
            <p>In short, I aim to collect as little data about you as possible. I minimize the use of cookies, choose privacy-friendly services, and carefully balance what data is collected.</p>
            
            <h2 class="mt-4 pt-4 border-top">For all visitors</h2>
            
            <h3 class="fs-5 fw-normal">Session Cookies</h3>
            <p>"where2fly_session" and "XSRF-token" are session cookies strictly required to perform and secure searches, and provide this service. They are deleted when the browser window is closed and do not collect or store personal data.</p>

            <h3 class="fs-5 fw-normal mt-3">Persistent Cookies</h3>
            <p>"remember_web_xxxxxx" is a cookie that remembers your login so you don't need to login each day. It is only set if you tick "remember me" at login, lasts up to 5 years or until you log out, and does not collect or store personal data beyond keeping you signed in.</p>

            <h3 class="fs-5 fw-normal mt-3">Browser data</h3>
            <p>Automatically recorded by our servers for security, optimization, and functionality purposes. This may include your IP address, browser type, and language.</p>
            
            <h3 class="fs-5 fw-normal mt-3">Local Storage</h3>
            <p>We use local storage on your device to save the map position and your map display preferences for your convenience. This data is stored locally on your device and is not transmitted to our servers or shared with any third parties.</p>

            <h3 class="fs-5 fw-normal mt-3">Map data</h3>
            <p>The map is drawn from tiles fetched by your browser directly from the providers listed below. That means your IP address is visible to them, in the same way it would be if you visited their websites. The terrain and precipitation layers can be turned off in the map's layer controls, in the top right corner of the map.</p>
            <ul>
                <li><u>CARTO:</u> the base map tiles, always loaded.</li>
                <li><u>Amazon S3 (Tilezen Terrain Tiles):</u> the elevation data behind the terrain relief layer.</li>
                <li><u>RainViewer:</u> the weather radar imagery behind the precipitation layer.</li>
            </ul>

            <h3 class="fs-5 fw-normal mt-3">Subprocessors</h3>
            <ul>
                <li><u>Scaleway:</u> EU-based service for sending out emails, ensuring that your email address is handled securely.</li>
                <li><u>Self-hosted Umami Analytics:</u> Used for website analytics with privacy in mind, without tracking your personal data.</li>
                <li><u>Sentry:</u> Used to catch and diagnose application errors. Personal data is not attached to error reports by default; some technical data (e.g. the page you were on, request metadata) may be captured incidentally when an error occurs. Sentry is based outside the EU/EEA.</li>
            </ul>
            <p>Some subprocessors and map tile providers listed on this page are located outside the EU/EEA. Where that is the case, please also refer to their own privacy policies for details on how they handle data.</p>

            <h2 class="mt-4 pt-4 border-top">For Where2Fly account holders</h2>
            <p>When you register an account, the following data is collected and used:</p>
            <ul>
                <li><u>E-mail address:</u> Used to verify your account, recover your account, notify you of security breaches, and send important service-related updates (not including marketing).</li>
                <li><u>Username:</u> Used for login and visible to other users as your identifier.</li>
                <li><u>Password:</u> Stored securely and hashed in the database.</li>
            </ul>
            <p><strong>Legal Basis for Processing:</strong> Your account data is processed because it is necessary to provide the service you signed up for (performance of a contract). Security- and fraud-related processing (e.g. detecting abuse) relies on our legitimate interest in keeping the service safe and functional.</p>
            <p><strong>Data Retention:</strong> Your data is stored only as long as necessary to provide the service or as required by law. Account data is retained until you choose to delete your account.</p>

            <p><strong>Your Rights:</strong> You have the right to access, rectify, or erase your personal data, restrict or object to its processing, and request data portability. To exercise these rights, please contact Blt950 on Discord.</p>
            <p><strong>Contact Us:</strong> For any questions or concerns about this privacy policy, please contact Blt950 on Discord.</p>
            <p><strong>Complaints:</strong> If you believe your data has been processed unlawfully, you have the right to lodge a complaint with a data protection supervisory authority, either in your own country of residence, or with <a href="https://www.datatilsynet.no" target="_blank" rel="noopener">Datatilsynet</a>, the Norwegian authority overseeing Where2Fly.</p>
        </div>        
    </div>
@endsection