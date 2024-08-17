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

                    @if(Session::has('error') OR isset($error))
                    <div class="alert alert-danger" role="alert">
                        <i class="fa fa-lg fa-exclamation-circle"></i> {!! Session::has('error') ? Session::pull("error") : $error !!}
                    </div>
                    @endif
                    
                    @if(Session::has('success') OR isset($success))
                    <div class="alert alert-success" role="alert">
                        {!! Session::has('success') ? Session::pull("success") : $error !!}
                    </div>
                    @endif

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
