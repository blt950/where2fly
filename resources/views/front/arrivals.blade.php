@extends('layouts.app')

@section('meta-description')
    <meta name="description" content="Always struggling to decide where to fly? Find some suggested destinations with fun weather and coverage!">
@endsection

@section('resources')
    @vite('resources/js/nouislider.js')
    @vite('resources/js/functions/tags.js')
@endsection

@section('content')
    @include('layouts.title', ['title' => 'Search for your flight', 'subtitle' => 'Find destinations based on your weather or coverage criteria'])

    <div class="container">
        @include('front.parts.tabs')
        @include('front.parts.form', ['icao' => 'departure', 'area' => 'destination'])
    </div>
@endsection

@section('js')
    @vite('resources/js/functions/tooltip.js')
    @vite('resources/js/functions/searchForm.js')
    @include('front.parts.sliders')
@endsection