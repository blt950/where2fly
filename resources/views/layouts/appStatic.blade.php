<!DOCTYPE html>
<html lang="en" class="h-100">
    <head>
        @include('layouts.header')
    </head>

    <body class="d-flex h-100 text-white bg-dark">

        <div class="d-flex w-100 h-100 mx-auto flex-column">

            @include('layouts.menu')

            <div class="d-flex flex-row">
                <nav class="sidebar @yield('sidebar-class')">
                    @yield('sidebar')
                </nav>
                <main class="nomap @yield('main-class')">

                     @if(Session::has('error') || isset($error))
                        <div class="alert alert-danger" role="alert">
                            <i class="fa-sharp fa-lg fa-exclamation-circle"></i> {!! Session::has('error') ? Session::pull("error") : $error !!}
                        </div>
                    @endif
                    
                    @if(Session::has('success') || isset($success))
                        <div class="alert alert-success" role="alert">
                            <i class="fa-sharp fa-lg fa-check-circle"></i>
                            {!! Session::has('success') ? Session::pull("success") : $success !!}
                        </div>
                    @endif

                    @yield('content')
                </main>
            </div> 
        </div>
        
        @yield('js')
        @vite('resources/js/functions/tooltip.js')
    </body>
</html>
