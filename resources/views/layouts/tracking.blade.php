@if(config('app.env') == 'production')
    <script defer data-domain="where2fly.today" src="{{ (isset($manualTracking) && $manualTracking) ? 'https://metrics.blt950.com/js/script.manual.js' : 'https://metrics.blt950.com/js/script.js' }}"></script>
    <script defer src="https://umami.blt950.com/script.js" data-website-id="2e068f9a-3125-41cd-aeba-b1cd74f20fd4" data-domains="where2fly.today" @isset($manualTracking) data-auto-track="false" @endisset></script>
@else
    <meta name="robots" content="noindex">
    <script defer data-domain="qa.where2fly.today" src="{{ (isset($manualTracking) && $manualTracking) ? 'https://metrics.blt950.com/js/script.manual.js' : 'https://metrics.blt950.com/js/script.js' }}"></script>
    <script defer src="https://umami.blt950.com/script.js" data-website-id="f9d5bb7d-b528-4c7c-8a85-5a401136ee29" @isset($manualTracking) data-auto-track="false" @endisset></script>
@endif

@isset($manualTracking)
    <script>window.plausible = window.plausible || function() { (window.plausible.q = window.plausible.q || []).push(arguments) }</script>
@endisset