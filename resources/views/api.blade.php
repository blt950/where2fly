@extends('layouts.app')

@section('meta-description')
    <meta name="description" content="Documentation of the API">
@endsection

@section('title', 'API Docs')
@section('content')

    @include('layouts.title', ['title' => 'API Documentation'])

    <div id="markdown" class="text-start">
        {{ Illuminate\Mail\Markdown::parse(file_get_contents(base_path() . '/API.md')) }}
    </div>

@endsection