<div class="container">

    <a href="https://github.com/blt950/where2fly/issues" class="btn btn-outline-success w-100 mb-3" target="_blank">
        Add a your new idea on Github <i class="fa-sharp fa-up-right-from-square"></i>
    </a>

    @foreach($issues as $issue)
        <a class="sidebar card-link d-block" href="{{ route('feedback.show', $issue['number']) }}">
            <div class="card mb-3">
                <div class="card-body {{ request()->route('id') == $issue['number'] ? 'active' : '' }} d-flex justify-content-between align-items-center gap-1">
                    <div>
                        @if(isset($userLastReadIssueNumber) && $userLastReadIssueNumber < $issue['number'])
                            <span class="badge bg-primary text-black font-work-sans mb-2">
                                New!
                            </span>
                        @endif
                        @if(in_array($issue['number'], $userVotes ?? []))
                            <span class="badge bg-success font-work-sans mb-2">
                                <i class="fa-sharp fa-check"></i>
                                Voted
                            </span>
                        @endif
                        <div class="card-title">
                            {{ $issue['title'] }}
                        </div>
                        <div class="d-flex align-items-center flex-wrap">
                            <img src="{{ $issue['user']['avatar_url'] }}" alt="" class="rounded-circle me-1">
                            <p class="card-text mb-0 me-2">{{ $issue['user']['login'] }}</p>
                            <p class="card-text text-muted mb-0">{{ \Carbon\Carbon::parse($issue['created_at'])->diffForHumans() }}</p>
                        </div>
                    </div>
                    <div class="upvotes d-flex flex-column align-items-center flex-shrink-0">
                        <div class="fs-3 fw-bold">{{ $groupedVotes[$issue['number']] ?? 0 }}</div>
                        Votes
                    </div>
                </div>
            </div>
        </a>
    @endforeach 
</div>