<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class FeedbackController extends Controller
{

    private function fetchIssues()
    {
        $response = Http::withToken(config('app.github_key'))->get('https://api.github.com/repos/blt950/where2fly/issues');
        return collect($response->json())->filter(function ($item) {
            return !isset($item['pull_request']);
        });
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $issues = $this->fetchIssues();
        return view('feedback.index', compact('issues'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $issues = $this->fetchIssues();
        $issue = $issues->firstWhere('number', $id);

        $response = Http::withToken(config('app.github_key'))->get("https://api.github.com/repos/blt950/where2fly/issues/{$id}/comments");
        $comments = $response->json();
        return view('feedback.show', compact('issues', 'issue', 'comments'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
