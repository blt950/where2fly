@extends('layouts.app')

@section('meta-description')
    <meta name="description" content="Find scenery for your flight simulator">
@endsection

@section('title', 'Scenery')
@section('content')

    @section('resources')
        @vite('resources/js/sortable.js')
    @endsection

    @include('layouts.title', ['title' => 'Scenery', 'subtitle' => 'Find scenery for your flight simulator'])
    
    <div class="container">

        <div class="d-block d-md-none">
            <p class="text-primary"><i class="fas fa-exclamation-triangle"></i> Scenery map is not yet available for mobile devices. Please use a desktop or tablet.</p>
        </div>

        <div class="d-none d-md-block">
            <div class="filterbox">
                <span class="m-0"><strong class="d-block">Filter</strong>
                    <a class="btn btn-sm {{ Route::is('scenery') ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('scenery') }}">All</a>
                    @foreach($simulators as $simulator)
                        <a class="btn btn-sm {{ optional($filteredSimulator)->id == $simulator->id ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('scenery.filtered', $simulator->shortened_name) }}">{{ $simulator->shortened_name }}</a>
                    @endforeach
                </span>
            </div>
    
            <p>{{ $airportsCount }} airports with scenery found. Click on the map to see details.</p>
        </div>        
    </div>
@endsection

@section('js')
    <script>
        var airportMapData = {!! isset($airportMapData) ? $airportMapData : '[]' !!}

        // Listen for the custom event indicating the map is ready
        window.addEventListener('mapReady', function() {
            setAirportsData(airportMapData);
        });
        
    </script>
@endsection