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
                    @foreach($scenery->simulators as $simulator)
                        @if($simulator->pivot->published == 1)
                            @continue
                        @endif
                        <li>
                            <span class="badge bg-dark">{{ $simulator->shortened_name }}</span>
                            <a href="{{ route('scenery.edit', [$scenery, $simulator]) }}">{{ $scenery->icao }}</a>
                            <span class="text-white-50">by {{ isset($simulator->pivot->suggested_by_user_id) ? App\Models\User::find($simulator->pivot->suggested_by_user_id)->username : 'System' }}</span>
                        </li>
                    @endforeach
                @endforeach
            </ul>
        @else
            <p>No scenery contributions</p>
        @endisset
    </div>
@endsection