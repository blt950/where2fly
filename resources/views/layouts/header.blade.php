<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Always struggling to decide where to fly? Find some suggested destinations with fun weather and coverage!">
<meta name="author" content="Blt950 / Daniel (1352906)">
<meta name="csrf-token" content="{{ csrf_token() }}">

<title> {{ config('app.name') }} | @yield('title', 'Home')</title>

<link rel="apple-touch-icon" sizes="180x180" href="/img/favicon/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="/img/favicon/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/img/favicon/favicon-16x16.png">
<link rel="manifest" href="/img/favicon/site.webmanifest">
<link rel="mask-icon" href="/img/favicon/safari-pinned-tab.svg" color="#5bbad5">
<link rel="shortcut icon" href="/img/favicon/favicon.ico">
<meta name="msapplication-TileColor" content="#2b5797">
<meta name="msapplication-config" content="/img/favicon/browserconfig.xml">
<meta name="theme-color" content="#d0c605">


@if(!empty(Config::get('app.gtag')))
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ Config::get('app.gtag') }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', '{{ Config::get('app.gtag') }}');
    </script>
@endif

@vite('resources/js/app.js')

@yield('resources')
@yield('css')