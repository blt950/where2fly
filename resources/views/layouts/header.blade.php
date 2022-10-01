<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<meta name="description" content="">
<meta name="author" content="">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="robots" content="noindex" />

<title>@yield('title', 'Home') | {{ config('app.name') }}</title>

@vite('resources/js/app.js')

@yield('css')