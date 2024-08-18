@extends('layouts.app')

@section('title', 'Account')
@section('content')

    @include('layouts.title', ['title' => 'Account Settings'])

    <div class="container">
        <a href="{{ route('user.logout') }}" class="btn btn-primary">Log out</a>
    </div>
@endsection

@section('js')
    @vite('resources/js/map.js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            initMap();
        })
    </script>
@endsection