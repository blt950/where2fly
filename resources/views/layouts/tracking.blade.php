@if(config('app.env') == 'production')
    <script defer data-domain="where2fly.today" src="{{ (isset($manualTracking) && $manualTracking) ? 'https://metrics.blt950.com/js/script.manual.js' : 'https://metrics.blt950.com/js/script.js' }}"></script>
@else
    <meta name="robots" content="noindex">
    <script defer data-domain="qa.where2fly.today" src="{{ (isset($manualTracking) && $manualTracking) ? 'https://metrics.blt950.com/js/script.manual.js' : 'https://metrics.blt950.com/js/script.js' }}"></script>
@endif

@isset($manualTracking)
    <script>window.plausible = window.plausible || function() { (window.plausible.q = window.plausible.q || []).push(arguments) }</script>
@endisset