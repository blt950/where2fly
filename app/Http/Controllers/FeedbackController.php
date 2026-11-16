<?php

namespace App\Http\Controllers;

use App\Models\FeedbackVote;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inertia\Inertia;

class FeedbackController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Update the user's last read issue number to the current highest issue number in the cache
        if (auth()->check()) {
            $user = auth()->user();
            $userLastReadIssueNumber = $user->feedback_last_read_number;
            $cacheHighestIssue = Cache::get('github_highest_issue', 0);
            if ($userLastReadIssueNumber < $cacheHighestIssue) {
                $user->feedback_last_read_number = $cacheHighestIssue;
                $user->save();
            }
        } else {
            $userLastReadIssueNumber = null;
        }

        [$issues, $groupedVotes, $userVotes] = $this->fetchIssuesAndVotes($userLastReadIssueNumber);

        return $this->render($issues, $groupedVotes, $userVotes, $userLastReadIssueNumber);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        [$issues, $groupedVotes, $userVotes] = $this->fetchIssuesAndVotes();
        $issue = $issues->firstWhere('number', $id);

        // If the issue is not found
        if (! $issue) {
            abort(404);
        }

        // Cache the comments for the issue to reduce API calls, cache for 10 minutes
        $comments = Cache::remember("github_issue_{$id}", 600, function () use ($id) {
            $response = Http::withToken(config('app.github_key'))->get("https://api.github.com/repos/blt950/where2fly/issues/{$id}/comments");
            if ($response->failed()) {
                abort(502);
            }

            return $response->json();
        });

        return $this->render($issues, $groupedVotes, $userVotes, null, $issue, $comments);
    }

    /**
     * The index and an issue are one page: the ideas in the sidebar, the chosen one beside it.
     * Markdown is rendered here, as before (raw HTML stripped, no unsafe links).
     */
    private function render(Collection $issues, Collection $groupedVotes, ?array $userVotes, ?int $lastRead, ?array $issue = null, array $comments = [])
    {
        $votes = fn (int $number) => (int) ($groupedVotes[$number] ?? 0);
        $markdown = fn (?string $body) => (string) Str::of($body ?? '')->markdown(['html_input' => 'strip', 'allow_unsafe_links' => false]);
        $author = fn (array $item) => [
            'login' => $item['user']['login'],
            'avatar' => $item['user']['avatar_url'],
            'created' => Carbon::parse($item['created_at'])->diffForHumans(),
        ];

        return Inertia::render('Feedback', [
            'issues' => $issues->map(fn (array $item) => [
                'number' => $item['number'],
                'title' => $item['title'],
                'votes' => $votes($item['number']),
                'isNew' => $lastRead !== null && $lastRead < $item['number'],
                'voted' => in_array($item['number'], $userVotes ?? []),
                'author' => $author($item),
            ])->values(),
            'issue' => $issue ? [
                'number' => $issue['number'],
                'title' => $issue['title'],
                'votes' => $votes($issue['number']),
                'voted' => in_array($issue['number'], $userVotes ?? []),
                'html' => $markdown($issue['body']),
                'author' => $author($issue),
                'url' => "https://github.com/blt950/where2fly/issues/{$issue['number']}",
                'comments' => collect($comments)->map(fn (array $comment) => [
                    'id' => $comment['id'] ?? null,
                    'html' => $markdown($comment['body']),
                    'author' => $author($comment),
                ])->values(),
            ] : null,
            'canVote' => auth()->check(),
            'loginUrl' => route('login'),
            'bugReports' => filled(config('sentry.dsn')),
        ])->withViewData(['title' => 'Feedback', 'metaDescription' => 'Have your say on what gets built next']);
    }

    /**
     * Store vote for the Github issue
     */
    public function storeVote()
    {
        $data = request()->validate([
            'github_issue_number' => 'required|integer',
        ]);

        // Check if the user has already voted for this issue
        $existingVote = FeedbackVote::where('user_id', auth()->id())
            ->where('github_issue_number', $data['github_issue_number'])
            ->first();

        if ($existingVote) {
            return back()->with(['error' => 'You have already voted for this issue.']);
        }

        // Create the vote
        FeedbackVote::create([
            'user_id' => auth()->id(),
            'github_issue_number' => $data['github_issue_number'],
        ]);

        return back()->with('success', 'Your vote has been recorded.');
    }

    /**
     * Remove the vote from the Github issue
     */
    public function destroyVote(string $id)
    {
        // Check if the vote exists
        $vote = FeedbackVote::where('user_id', auth()->id())
            ->where('github_issue_number', $id)
            ->first();

        if (! $vote) {
            return back()->with(['error' => 'Could not find vote to remove.']);
        }

        // Delete the vote
        $vote->delete();

        return back()->with('success', 'Your vote has been removed.');
    }

    /**
     * Fetch issues and votes, used for the index page to avoid multiple calls in the loop
     */
    private function fetchIssuesAndVotes(?int $userLastReadIssueNumber = null)
    {
        [$groupedVotes, $userVotes] = $this->fetchVotes();
        $issues = $this->fetchIssues();

        // Sort issues by number of votes (descending) and then by creation date (descending)
        $issues = $issues->sort(function ($a, $b) use ($groupedVotes, $userLastReadIssueNumber) {
            $aIsNew = isset($userLastReadIssueNumber) && $userLastReadIssueNumber < $a['number'];
            $bIsNew = isset($userLastReadIssueNumber) && $userLastReadIssueNumber < $b['number'];

            if ($aIsNew !== $bIsNew) {
                return $aIsNew ? -1 : 1; // new issues first
            }

            $aVotes = (int) ($groupedVotes[$a['number']] ?? 0);
            $bVotes = (int) ($groupedVotes[$b['number']] ?? 0);

            if ($aVotes !== $bVotes) {
                return $bVotes <=> $aVotes; // higher votes second
            }

            return strtotime($b['created_at']) <=> strtotime($a['created_at']); // newer last
        })->values();

        return [$issues, $groupedVotes, $userVotes];
    }

    /**
     * Fetch issues from GitHub and filter out pull requests
     */
    private function fetchIssues()
    {
        // Cache the issues for 10 minutes to reduce API calls
        $data = Cache::remember('github_issues', 600, function () {
            $request = Http::withToken(config('app.github_key'))->get('https://api.github.com/repos/blt950/where2fly/issues');
            if ($request->failed()) {
                abort(502);
            }

            return $request->json();
        });

        // Filter out pull requests and only keep issues with the "open for vote" label
        return collect($data)->filter(function ($item) {
            if (isset($item['pull_request'])) {
                return false;
            }

            return collect($item['labels'] ?? [])->contains(function ($label) {
                return strtolower($label['name'] ?? '') === 'open for vote';
            });
        });
    }

    /*
     * Fetch votes from the database, group them by issue number and count them
     * Also fetch the votes of the current user to highlight them in the UI
     */
    private function fetchVotes()
    {
        $allVotes = FeedbackVote::all();

        // Group votes by issue number and count them
        $groupedVotes = $allVotes->groupBy('github_issue_number')->map->count();

        // Get the votes of the current user
        $userVotes = auth()->check() ? $allVotes->where('user_id', auth()->id())->pluck('github_issue_number')->toArray() : null;

        return [$groupedVotes, $userVotes];
    }
}
