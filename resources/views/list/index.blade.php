@extends('layouts.app')
@section('title', 'My Lists')
@section('content')

    @include('layouts.title', ['title' => 'My Lists'])

    <div class="container">

        <p class="font-work-sans mb-5">Create lists of airports to whitelist during search and see on the map. Perfect way to only find destinations you're interested in.</p>
        
        @foreach($lists as $list)
        <div class="d-flex justify-content-between font-size-1rem mt-2">
            <div class="d-flex list-title">
                <div style="background: {{ $list->color }}"></div>
                <strong>{{ $list->name }}</strong>
            </div>
            <span>{{ $list->airports_count }} airports</span>
            <div>
                <a href="{{ route('list.edit', $list) }}" class="btn btn-outline-primary"><i class="fas fa-pencil"></i> Edit</a>
            </div>
        </div>
        @endforeach
        
        <a href="{{ route('list.create') }}" class="btn btn-success mt-4"><i class="fas fa-plus"></i> Create a new list</a>
    </div>
@endsection

@section('js')
    @vite('resources/js/functions/taf.js')
    @vite('resources/js/cards.js')
    @vite('resources/js/map.js')
    @include('scripts.defaultMap')
@endsection