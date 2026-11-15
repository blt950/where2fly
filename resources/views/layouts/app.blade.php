<!DOCTYPE html>
<html lang="en" class="h-100">
    <head>
        @include('layouts.header')
    </head>

    <body class="d-flex h-100 text-white bg-dark has-bottom-nav">

        <div class="d-flex w-100 h-100 mx-auto flex-column">

            @include('layouts.menu')

            <div class="d-flex flex-row">
                <main>

                    @include('layouts.alerts')

                    @yield('content')
                </main>

                <div>
                    @include('parts.map')
                    @include('layouts.footer')
                </div>
            </div>
        </div>
        
        @include('layouts.bottomnav')

        @yield('js')
        @vite('resources/js/functions/tooltip.js')
    </body>
</html>
