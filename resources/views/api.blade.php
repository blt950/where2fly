@extends('layouts.app')

@section('meta-description')
<meta name="description" content="Documentation of the API">
@endsection

@section('title', 'API Docs')
@section('content')

<div class="cover-container d-flex w-100 h-100 p-3 mx-auto flex-column">

    @include('layouts.menu')
  
    <main>
        <h1 class="mb-3 mt-5">API Documentation</h1>
        
        <div id="markdown" class="text-start">
            {{ Illuminate\Mail\Markdown::parse(file_get_contents(base_path() . '/API.md')) }}
        </div>

    </main>
  
    @include('layouts.footer')
</div>

@endsection