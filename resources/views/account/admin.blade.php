@extends('layouts.app')

@section('title', 'Admin')
@section('content')

    @include('layouts.title', ['title' => 'Admin'])

    <div class="container">
        <h2>Stats</h2>
        <ul>
            <li>Users: {{ $usersCount }}</li>
            <li>Lists: {{ $listsCount }}</li>
            <li>Sceneries: {{ $sceneriesCount }}</li>
        </ul>

        <h2>Scenery Contributions</h2>
        @isset($sceneries)
            <ul>
                @foreach($sceneries as $scenery)
                    <li>
                        <a href="{{ route('scenery.edit', $scenery) }}">{{ $scenery->icao }}</a>
                        <span class="text-white-50">by {{ $scenery->author }}</span>
                    </li>
                @endforeach
            </ul>
        @else
            <p>No scenery contributions</p>
        @endisset
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