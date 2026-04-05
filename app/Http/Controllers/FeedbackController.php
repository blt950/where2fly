<?php

namespace App\Http\Controllers;

use App\Models\FeedbackVote;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class FeedbackController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        [$issues, $groupedVotes, $userVotes] = $this->fetchIssuesAndVotes();

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

        return view('feedback.index', compact('issues', 'groupedVotes', 'userVotes', 'userLastReadIssueNumber'));
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

        return view('feedback.show', compact('issues', 'issue', 'comments', 'groupedVotes', 'userVotes'));
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
        $existingVote = FeedbackVote::where('user_id', auth()->user()->id)
            ->where('github_issue_number', $data['github_issue_number'])
            ->first();

        if ($existingVote) {
            return back()->with(['error' => 'You have already voted for this issue.']);
        }

        // Create the vote
        FeedbackVote::create([
            'user_id' => auth()->user()->id,
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
        $vote = FeedbackVote::where('user_id', auth()->user()->id)
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
    private function fetchIssuesAndVotes()
    {
        [$groupedVotes, $userVotes] = $this->fetchVotes();
        $issues = $this->fetchIssues();

        // Sort issues by number of votes (descending) and then by creation date (descending)
        $issues = $issues->sort(function ($a, $b) use ($groupedVotes) {
            $aVotes = (int) ($groupedVotes[$a['number']] ?? 0);
            $bVotes = (int) ($groupedVotes[$b['number']] ?? 0);

            if ($aVotes !== $bVotes) {
                return $bVotes <=> $aVotes; // higher votes first
            }

            return strtotime($b['created_at']) <=> strtotime($a['created_at']); // newer first
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
        $groupedVotes = $allVotes->groupBy('github_issue_number')->map(function ($votes) {
            return $votes->count();
        });

        // Get the votes of the current user
        $userVotes = auth()->user() ? $allVotes->where('user_id', auth()->user()->id)->pluck('github_issue_number')->toArray() : null;

        return [$groupedVotes, $userVotes];
    }
}
