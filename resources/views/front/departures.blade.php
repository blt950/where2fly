@extends('layouts.app')

@section('meta-description')
<meta name="description" content="Always struggling to decide where to fly? Find some suggested destinations with fun weather and coverage!">
@endsection

@section('resources')
@vite('resources/js/nouislider.js')
@vite('resources/js/multiselect.js')
@endsection

@section('content')

<div class="cover-container text-center d-flex w-100 h-100 p-3 mx-auto flex-column">
    
    <div>
        @include('layouts.menu')
    
        <main class="front">
            @include('front.parts.top')
            @include('front.parts.airport.form', ['icao' => 'arrival', 'area' => 'origin'])
        </main>
    </div>
        
    @include('scripts.search')
    @include('front.parts.airport.script')
    
    @include('layouts.footer')
</div>

@endsection