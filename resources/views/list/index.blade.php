@extends('layouts.app')
@section('title', 'My Lists')
@section('content')

    @include('layouts.title', ['title' => 'My Lists'])

    <div class="container">

        <p class="font-work-sans mb-5">Create lists of airports to whitelist during search and see on the map. Perfect way to only find destinations you're interested in.</p>
        
        @foreach($lists as $list)
        <div class="d-flex justify-content-between font-size-1rem mt-4">
            <div class="d-flex flex-column">
                <div class="d-flex list-title {{ $list->hidden ? 'hidden' : '' }}">
                    <div style="background: {{ $list->color }}"></div>
                    <strong>{{ $list->name }}</strong>
                </div>
                <span class="list-airports {{ $list->hidden ? 'hidden' : '' }}"><i class="fas fa-joystick"></i>&nbsp;{{ $list->simulator->shortened_name }}</span>
                <span class="list-airports {{ $list->hidden ? 'hidden' : '' }}"><i class="fas fa-tower-control"></i>&nbsp;{{ $list->airports_count }} airports {{ $list->hidden ? '(Hidden)' : '' }}</span>
            </div>
            <div>
                @if($list->hidden)
                    <a href="{{ route('list.toggle', $list) }}" class="btn btn-outline-light"><i class="fas fa-eye"></i> Show</a>
                @else
                    <a href="{{ route('list.toggle', $list) }}" class="btn btn-outline-light"><i class="fas fa-eye-slash"></i> Hide</a>
                @endif
                
                <a href="{{ route('list.edit', $list) }}" class="btn btn-outline-primary"><i class="fas fa-pencil"></i> Edit</a>
            </div>
        </div>
        @endforeach
        
        <a href="{{ route('list.create') }}" class="btn btn-success mt-5"><i class="fas fa-plus"></i> Create a new list</a>
    </div>
@endsection