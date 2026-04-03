@extends('layouts.app-nomap')

@section('meta-description')
    <meta name="description" content="Provide feedback and upvote features for Where2Fly">
@endsection

@section('title', 'Feedback')

@section('sidebar')
    @include('layouts.title', ['title' => 'Feedback', 'subtitle' => 'Upvote features and give your own suggestions for Where2Fly'])
    @include('feedback.sidebar', ['issues' => $issues])
@endsection

@section('content')
    <div class="feedback-container d-flex justify-content-center align-items-center">   
        <div class="text-center">
            <p class="fs-2">Choose a card from the sidebar for details and actions</p>
        </div>
    </div>
@endsection