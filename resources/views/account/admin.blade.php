@extends('layouts.app')

@section('title', 'Admin')
@section('content')

    @include('layouts.title', ['title' => 'Admin'])

    <div class="container">
        <h2>Stats</h2>
        <ul>
            <li>Users: {{ $usersCount }}</li>
            <li>Lists: {{ $listsCount }}</li>
        </ul>
    </div>

    @isset($airportsMapCollection)
        @include('parts.popupContainer', ['airportsMapCollection' => ($airportsMapCollection)])
    @endisset
@endsection

@section('js')
    @vite('resources/js/functions/taf.js')
    @vite('resources/js/cards.js')
    @vite('resources/js/map.js')
    @include('scripts.defaultMap')
@endsection