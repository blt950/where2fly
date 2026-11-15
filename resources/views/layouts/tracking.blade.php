{{-- Ids in URLs (a list being edited) never reach analytics: umami calls this before every send,
     including the page views the Inertia shell records on client-side visits --}}
<script>
    window.w2fBeforeSend = (type, payload) => {
        if (payload?.url) payload.url = payload.url.replace(/^\/lists\/\d+\/edit/, '/lists/x/edit');
        return payload;
    };
</script>
@if(app()->isProduction())
    <script defer src="https://metrics.blt950.com/script.js" data-website-id="{{ config('umami.website_id_prod') }}" data-domains="where2fly.today" data-performance="true" data-before-send="w2fBeforeSend" @isset($manualTracking) data-auto-track="false" @endisset></script>
@else
    <meta name="robots" content="noindex">
    <script defer src="https://metrics.blt950.com/script.js" data-website-id="{{ config('umami.website_id_dev') }}" data-before-send="w2fBeforeSend" @isset($manualTracking) data-auto-track="false" @endisset></script>
@endif
