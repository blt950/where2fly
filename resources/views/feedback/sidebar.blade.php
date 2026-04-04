<div class="container">
    <div class="alert alert-info mb-3" role="alert">
        <p class="mb-0"><i class="fa-sharp fa-lightbulb-on"></i> Do you have a suggestion? Share it on <a class="text-secondary" href="https://github.com/blt950/where2fly/issues" target="_blank">GitHub</a> and it'll be available for vote here after a review.</p>
    </div>

    @foreach($issues as $issue)
        <a class="sidebar card-link d-block" href="{{ route('feedback.show', $issue['number']) }}">
            <div class="card mb-3">
                <div class="card-body {{ request()->route('id') == $issue['number'] ? 'active' : '' }} d-flex justify-content-between align-items-center gap-1">
                    <div>
                        <div class="card-title">{{ $issue['title'] }}</div>
                        <div class="d-flex align-items-center flex-wrap">
                            <img src="{{ $issue['user']['avatar_url'] }}" alt="" class="rounded-circle me-1">
                            <p class="card-text mb-0 me-2">{{ $issue['user']['login'] }}</p>
                            <p class="card-text text-muted mb-0">{{ \Carbon\Carbon::parse($issue['created_at'])->diffForHumans() }}</p>
                        </div>
                    </div>
                    <div class="upvotes d-flex flex-column align-items-center flex-shrink-0">
                        <div class="fs-3 fw-bold">9</div>
                        Votes
                    </div>
                </div>
            </div>
        </a>
    @endforeach 
</div>