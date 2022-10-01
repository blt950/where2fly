<!DOCTYPE html>
<html lang="en">
    <head>
        @include('layouts.header')
    </head>

    <body>
    <div id='app'></div>

    <div id="wrapper">

        @include('layouts.sidebar')
        @yield('content')
    </div>

    @yield('js')
    </body>
</html>
