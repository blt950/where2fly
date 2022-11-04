<!DOCTYPE html>
<html lang="en" class="h-100">
    <head>
        @include('layouts.header')
    </head>

    <body class="d-flex h-100 text-center text-white bg-dark">
        @yield('content')
        @yield('js')

        @if(!empty(Config::get('app.gtag')))
            @include('scripts.cookie')
        @endif
    </body>
</html>
