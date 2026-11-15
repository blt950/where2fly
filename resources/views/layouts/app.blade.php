{{-- The few pages still served by Blade (admin, scenery create/edit): forms that need no map, so a
     plain page with a way back. Everything else is the Inertia shell (inertia.blade.php). --}}
<!DOCTYPE html>
<html lang="en">
    <head>
        @include('layouts.header')
    </head>

    <body class="text-white bg-dark simple-page">
        <header class="simple-header">
            <a href="{{ route('front') }}" class="navbar-brand logo">Where2Fly</a>
            <a href="{{ route('front') }}" class="simple-back"><i class="fa-sharp fa-arrow-left" aria-hidden="true"></i> Back to the map</a>
        </header>

        <main class="simple-main">
            @foreach($alerts['messages'] as $message)
                <div class="alert alert-{{ $message['variant'] }}" role="alert">{{ $message['text'] }}</div>
            @endforeach

            @yield('content')
        </main>

        @yield('js')
    </body>
</html>
