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
                        <span class="badge bg-dark">{{ $scenery->simulator->shortened_name }}</span>
                        <a href="{{ route('scenery.edit', [$scenery]) }}">{{ $scenery->developer->icao }}</a>
                        <span class="text-white-50">by {{ isset($scenery->suggested_by_user_id) ? App\Models\User::find($scenery->suggested_by_user_id)->username : 'System' }}</span>
                    </li>
                @endforeach
            </ul>
        @else
            <p>No scenery contributions</p>
        @endisset
    </div>
@endsection