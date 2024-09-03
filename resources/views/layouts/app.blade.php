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

                    @if(Session::has('error') || isset($error))
                        <div class="alert alert-danger" role="alert">
                            <i class="fa fa-lg fa-exclamation-circle"></i> {!! Session::has('error') ? Session::pull("error") : $error !!}
                        </div>
                    @endif

                    @if(Session::has('warning') || isset($warning))
                        <div class="alert alert-warning" role="alert">
                            {!! Session::has('warning') ? Session::pull("warning") : $warning !!}
                        </div>
                    @endif
                    
                    @if(Session::has('success') || isset($success))
                        <div class="alert alert-success" role="alert">
                            {!! Session::has('success') ? Session::pull("success") : $success !!}
                        </div>
                    @else
                        @auth
                            @if(auth()->user()->email_verified_at == null)
                                <div class="alert alert-warning" role="alert">
                                    Your account is awaiting email verification.
                                    
                                    <form method="POST" action="{{ route('verification.send') }}">
                                        @csrf
                                        <button class="btn btn-sm btn-primary text-secondary mt-2">Resend verification email</button>
                                    </form>
                                </div>
                            @endif
                        @endauth
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
