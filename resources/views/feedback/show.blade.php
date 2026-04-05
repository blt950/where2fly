@extends('layouts.appStatic')

@section('meta-description')
    <meta name="description" content="Have your say on what gets built next">
@endsection

@section('title', 'Feedback')

@section('sidebar')
    @include('layouts.title', ['title' => 'Feedback', 'subtitle' => 'Have your say on what gets built next'])
    @include('feedback.sidebar', ['issues' => $issues, 'groupedVotes' => $groupedVotes])
@endsection

@section('sidebar-class', 'mobile-nofocus')
@section('main-class', 'mobile-focus')

@section('content')
    <div class="feedback-container d-flex flex-column justify-content-center align-items-center gap-3">
        
        
        <div class="card">
            <div class="card-title">
                <div class="d-flex flex-row justify-content-between align-items-center flex-wrap">

                    <div class="d-flex flex-column">
                        <h3 class="mb-1">{{ $issue['title'] }}</h3>
                    </div>

                    <div class="w-200 align-items-center d-flex flex-row gap-1">
                        <div class="upvotes d-flex flex-row align-items-center justify-content-center gap-1">
                            <span class="fw-bold">{{ $groupedVotes[$issue['number']] ?? 0 }}</span> {{ ($groupedVotes[$issue['number']] ?? 0) === 1 ? 'Vote' : 'Votes' }}
                        </div>
                        @auth 
                            @if($userVotes && in_array($issue['number'], $userVotes))
                                <a href="{{ route('feedback.vote.delete', ['id' => $issue['number']]) }}" class="btn btn-outline-primary btn-sm me-2"><i class="fa-sharp fa-xmark"></i> Remove vote</a>
                            @else
                                <form action="{{ route('feedback.vote') }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="github_issue_number" value="{{ $issue['number'] }}">
                                    <button type="submit" class="btn btn-primary btn-sm me-2"><i class="fa-sharp fa-thumbs-up"></i> Add Vote</button>
                                </form>
                            @endif
                        @else
                            <a href="{{ route('login') }}" class="btn btn-primary btn-sm me-2"><i class="fa-sharp fa-lock"></i> Log in to vote</a>
                        @endauth
                    </div>
                </div>
            </div>    
    
            <div class="card-body">
                {!! Str::of($issue['body'])->markdown([
                    'html_input' => 'strip',
                    'allow_unsafe_links' => false,
                ])
                !!}
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
                    {!! Str::of($comment['body'])->markdown([
                        'html_input' => 'strip',
                        'allow_unsafe_links' => false,
                    ])
                    !!}
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

        <a href="https://github.com/blt950/where2fly/issues/{{ $issue['number'] }}" class="btn btn-info" target="_blank">
            Discuss this idea on GitHub <i class="fa-sharp fa-up-right-from-square"></i>
        </a>
        
    </div>
@endsection