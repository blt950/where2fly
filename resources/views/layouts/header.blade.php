<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
@yield('meta-description')
<meta name="author" content="Blt950 / Daniel (1352906)">
<meta name="csrf-token" content="{{ csrf_token() }}">

@hasSection('title')
    <title>{{ config('app.name') }} | @yield('title', 'Home')</title>
@else
    <title>{{ config('app.name') }}</title>
@endif

<link rel="apple-touch-icon" sizes="180x180" href="/img/favicon/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="/img/favicon/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/img/favicon/favicon-16x16.png">
<link rel="manifest" href="/img/favicon/site.webmanifest">
<link rel="mask-icon" href="/img/favicon/safari-pinned-tab.svg" color="#5bbad5">
<link rel="shortcut icon" href="/img/favicon/favicon.ico">
<meta name="msapplication-TileColor" content="#ddb81c">
<meta name="msapplication-config" content="/img/favicon/browserconfig.xml">
<meta name="theme-color" content="#ddb81c">

<meta property="og:title" content="Where2Fly">
<meta property="og:description" content="Always struggling to decide where to fly? Find some suggested destinations with fun weather and coverage!">
<meta property="og:type" content="website">
<meta property="og:image" content="https://where2fly.today/img/thumb.jpg">
<meta property="og:image:type" content="image/jpg">

@if(config('app.env') == 'production')
    <script defer data-domain="where2fly.today" src="https://metrics.blt950.com/js/script.js"></script>
@else
    <meta name="robots" content="noindex">
    <script defer data-domain="qa.where2fly.today" src="https://metrics.blt950.com/js/script.js"></script>
@endif

@viteReactRefresh
@vite('resources/js/app.js')

@yield('resources')
@yield('css')