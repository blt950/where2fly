@if(config('app.env') == 'production')
    <script defer src="https://umami.blt950.com/script.js" data-website-id="{{ config('umami.website_id_prod') }}" data-domains="where2fly.today" @isset($manualTracking) data-auto-track="false" @endisset></script>
@else
    <meta name="robots" content="noindex">
    <script defer src="https://umami.blt950.com/script.js" data-website-id="{{ config('umami.website_id_dev') }}" @isset($manualTracking) data-auto-track="false" @endisset></script>
@endif