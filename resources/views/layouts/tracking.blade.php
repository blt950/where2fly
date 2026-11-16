{{-- Ids in URLs (a list or scenery being edited) never reach analytics: umami calls this before every send,
     including the shell's client-side page views. It sends full URLs, and the referrer carries the id too. --}}
<script>
    (() => {
        let lastView = null;
        const mask = (url) => url?.replace(/\/(lists|scenery)\/\d+\/edit/, '/$1/x/edit')
            .replace(/\/account\/recovery\/reset\/[^#]*/, '/account/recovery/reset/x')
            .replace(/([?&])airport=[^&#]*&?/, '$1').replace(/[?&]$/, '');

        window.w2fBeforeSend = (type, payload) => {
            if (!payload) return payload;
            Object.assign(payload, { url: mask(payload.url), referrer: mask(payload.referrer) });

            // The selected airport lives in the URL (?airport=): opening a card is not a page view
            if (type === 'event' && !payload.name) {
                if (payload.url === lastView) return false;
                lastView = payload.url;
            }

            return payload;
        };
    })();
</script>
@if(app()->isProduction())
    <script defer src="https://metrics.blt950.com/script.js" data-website-id="{{ config('umami.website_id_prod') }}" data-domains="where2fly.today" data-performance="true" data-before-send="w2fBeforeSend"></script>
@else
    <meta name="robots" content="noindex">
    <script defer src="https://metrics.blt950.com/script.js" data-website-id="{{ config('umami.website_id_dev') }}" data-before-send="w2fBeforeSend"></script>
@endif
