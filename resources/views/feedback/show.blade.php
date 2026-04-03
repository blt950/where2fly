@extends('layouts.app-nomap')

@section('meta-description')
    <meta name="description" content="Feedback details">
@endsection

@section('title', 'Feedback')

@section('sidebar')
    @include('layouts.title', ['title' => 'Feedback', 'subtitle' => 'Upvote features and give your own suggestions for Where2Fly'])
    @include('feedback.sidebar', ['issues' => $issues])
@endsection

@section('content')
    <div class="feedback-container d-flex flex-column justify-content-center align-items-center gap-3">
        
        <div class="card">
            <div class="card-title">
                <div class="d-flex flex-row justify-content-between align-items-center">

                    <div class="d-flex flex-column">
                        <h3 class="mb-1">{{ $issue['title'] }}</h3>
                    </div>

                    <div class="w-200 d-flex flex-row align-items-space-between gap-1">
                        <div class="upvotes d-flex flex-row align-items-center justify-content-center gap-2">
                            <span class="fs-3 fw-bold">19</span> Votes
                        </div>
                        <button class="btn btn-primary btn-sm me-2"><i class="fa-sharp fa-thumbs-up"></i> Add Vote</button>
                    </div>
                </div>            
            </div>    
    
            <div class="card-body">
                {{ Illuminate\Mail\Markdown::parse($issue['body']) }}
            </div>

            <div class="card-footer">
                <div class="d-flex align-items-center">
                    <img src="{{ $issue['user']['avatar_url'] }}" alt="" class="rounded-circle me-1">
                    <p class="card-text mb-0 me-2">{{ $issue['user']['login'] }}</p>
                    <p class="card-text text-muted mb-0">{{ \Carbon\Carbon::parse($issue['created_at'])->diffForHumans() }}</p>
                </div>
            </div>
        </div>

        @foreach($comments as $comment)
            <div class="card">
                <div class="card-body">
                    {{ Illuminate\Mail\Markdown::parse($comment['body']) }}
                </div>
        
                <div class="card-footer">
                    <div class="d-flex align-items-center">
                        <img src="{{ $comment['user']['avatar_url'] }}" alt="" class="rounded-circle me-1">
                        <p class="card-text mb-0 me-2">{{ $comment['user']['login'] }}</p>
                        <p class="card-text text-muted mb-0">{{ \Carbon\Carbon::parse($comment['created_at'])->diffForHumans() }}</p>
                    </div>
                </div>
            </div>
        @endforeach

        <div class="alert alert-info mb-3 w-80" role="alert">
            <p class="mb-0">You may add to this conversation via the <a class="text-secondary" href="https://github.com/blt950/where2fly/issues/{{ $issue['number'] }}" target="_blank">GitHub issue <i class="fa-sharp fa-up-right-from-square"></i></a></p>
        </div>
        
    </div>
@endsection