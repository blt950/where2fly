<!DOCTYPE html>
<html lang="en" class="h-100">
    <head>
        @section('meta-description')
            <meta name="description" content="{{ $metaDescription ?? '' }}">
        @endsection
        @include('layouts.header', ['viteEntry' => 'resources/js/inertia.jsx', 'inertiaManagedTitle' => true])
        <x-inertia::head />
    </head>

    <body class="d-flex h-100 text-white bg-dark">
        <x-inertia::app />
    </body>
</html>
