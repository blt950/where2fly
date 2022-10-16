<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="">
<meta name="author" content="">
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>@yield('title', 'Home') | {{ config('app.name') }}</title>

<!-- Favicons -->
<!-- Todo -->
<meta name="theme-color" content="#7952b3">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">

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

<style>
    .bd-placeholder-img {
        font-size: 1.125rem;
        text-anchor: middle;
        -webkit-user-select: none;
        -moz-user-select: none;
        user-select: none;
    }

    @media (min-width: 768px) {
        .bd-placeholder-img-lg {
            font-size: 3.5rem;
        }
    }
</style>

@vite('resources/js/app.js')

@yield('css')