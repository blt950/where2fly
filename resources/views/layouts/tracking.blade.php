{{-- Ids in URLs (a list being edited) never reach analytics: umami calls this before every send,
     including the shell's client-side page views. It sends full URLs, and the referrer carries the id too. --}}
<script>
    window.w2fBeforeSend = (type, payload) => {
        const mask = (url) => url?.replace(/\/lists\/\d+\/edit/, '/lists/x/edit');
        if (payload) Object.assign(payload, { url: mask(payload.url), referrer: mask(payload.referrer) });
        return payload;
    };
</script>
@if(app()->isProduction())
    <script defer src="https://metrics.blt950.com/script.js" data-website-id="{{ config('umami.website_id_prod') }}" data-domains="where2fly.today" data-performance="true" data-before-send="w2fBeforeSend" @isset($manualTracking) data-auto-track="false" @endisset></script>
@else
    <meta name="robots" content="noindex">
    <script defer src="https://metrics.blt950.com/script.js" data-website-id="{{ config('umami.website_id_dev') }}" data-before-send="w2fBeforeSend" @isset($manualTracking) data-auto-track="false" @endisset></script>
@endif
