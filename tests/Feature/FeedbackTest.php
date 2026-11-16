<?php

namespace Tests\Feature;

use App\Models\FeedbackVote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FeedbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $issue = fn (int $number, string $title, string $body = 'Body') => [
            'number' => $number, 'title' => $title, 'body' => $body,
            'created_at' => now()->subDay()->toIso8601String(),
            'user' => ['login' => 'pilot', 'avatar_url' => 'https://example.com/a.png'],
            'labels' => [['name' => 'Open for vote']],
        ];

        Http::fake([
            'api.github.com/repos/blt950/where2fly/issues/7/comments' => Http::response([
                ['id' => 1, 'body' => 'A **comment**', 'created_at' => now()->toIso8601String(), 'user' => ['login' => 'atc', 'avatar_url' => 'https://example.com/b.png']],
            ]),
            'api.github.com/repos/blt950/where2fly/issues' => Http::response([
                $issue(7, 'Night mode', "Please add it <script>alert(1)</script>\n\n[x](javascript:alert(1))"),
                $issue(9, 'More airports'),
                ['number' => 10, 'title' => 'A pull request', 'pull_request' => [], 'labels' => [['name' => 'Open for vote']]],
            ]),
        ]);
    }

    public function test_the_index_lists_open_ideas_by_votes(): void
    {
        FeedbackVote::create(['user_id' => User::factory()->create()->id, 'github_issue_number' => 9]);

        $this->get('/feedback')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Feedback')
            ->where('issue', null)
            ->where('issues', fn ($issues) => collect($issues)->pluck('number')->all() === [9, 7])
            ->where('issues.0.votes', 1)
            ->where('canVote', false));
    }

    public function test_an_idea_renders_its_markdown_without_raw_html_or_unsafe_links(): void
    {
        $this->get('/feedback/7')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('issue.title', 'Night mode')
            ->where('issue.html', fn ($html) => ! str_contains($html, '<script') && ! str_contains($html, 'javascript:'))
            ->where('issue.comments.0.html', fn ($html) => str_contains($html, '<strong>comment</strong>')));
    }

    public function test_an_unknown_idea_is_a_404(): void
    {
        $this->get('/feedback/123')->assertNotFound();
    }

    public function test_voting_and_removing_a_vote(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from('/feedback/7')->post('/feedback/vote', ['github_issue_number' => 7])
            ->assertRedirect('/feedback/7')->assertSessionHas('success');
        $this->actingAs($user)->get('/feedback/7')->assertInertia(fn (Assert $page) => $page
            ->where('issue.voted', true)->where('issue.votes', 1)->where('canVote', true));

        $this->actingAs($user)->from('/feedback/7')->get('/feedback/vote/7/delete')->assertRedirect('/feedback/7');
        $this->assertDatabaseMissing('feedback_votes', ['user_id' => $user->id, 'github_issue_number' => 7]);
    }
}
