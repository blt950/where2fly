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

        if(auth()->check()) {
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

        $response = Http::withToken(config('app.github_key'))->get("https://api.github.com/repos/blt950/where2fly/issues/{$id}/comments");
        $comments = $response->json();

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

        $existingVote = FeedbackVote::where('user_id', auth()->user()->id)
            ->where('github_issue_number', $data['github_issue_number'])
            ->first();

        if ($existingVote) {
            return back()->with(['error' => 'You have already voted for this issue.'], 400);
        }

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
        $vote = FeedbackVote::where('user_id', auth()->user()->id)
            ->where('github_issue_number', $id)
            ->first();

        if (! $vote) {
            return back()->with(['error' => 'Could not find vote to remove.'], 404);
        }

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
        $response = Http::withToken(config('app.github_key'))->get('https://api.github.com/repos/blt950/where2fly/issues');

        return collect($response->json())->filter(function ($item) {
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

        $groupedVotes = $allVotes->groupBy('github_issue_number')->map(function ($votes) {
            return $votes->count();
        });

        $userVotes = auth()->user() ? $allVotes->where('user_id', auth()->user()->id)->pluck('github_issue_number')->toArray() : null;

        return [$groupedVotes, $userVotes];
    }
}
