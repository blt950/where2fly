@extends('layouts.appStatic')

@section('meta-description')
    <meta name="description" content="Have your say on what gets built next">
@endsection

@section('title', 'Feedback')

@section('sidebar')
    @include('layouts.title', ['title' => 'Feedback', 'subtitle' => 'Have your say on what gets built next'])
    @include('feedback.sidebar', ['issues' => $issues])
@endsection

@section('sidebar-class', 'mobile-focus')
@section('main-class', 'mobile-nofocus')

@section('content')
    <div class="feedback-container action-text d-flex justify-content-center align-items-center">   
        <div class="text-center">
            <p class="fs-2 m-0">Choose a card from the sidebar for details and actions</p>
        </div>
    </div>
@endsection

