<!DOCTYPE html>
<html lang="en" class="h-100">
    <head>
        @include('layouts.header')
    </head>

    <body class="d-flex h-100 text-white bg-dark">

        <div class="d-flex w-100 h-100 mx-auto flex-column">

            @include('layouts.menu')

            <div class="d-flex flex-row">
                <main>
                    @yield('content')
                </main>

                <div>
                    @include('parts.map')
                    @include('layouts.footer')
                </div>
            </div>
        </div>
        
        @yield('js')
    </body>
</html>
