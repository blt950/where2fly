<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ContentPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_changelog_renders_its_blade_body_inside_the_shell(): void
    {
        $this->get('/changelog')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Content')
            ->where('title', 'Changelog')
            ->where('html', fn ($html) => str_contains($html, '<h2 class="mb-0">v3.1.7</h2>')));
    }

    public function test_privacy_policy_keeps_the_map_providers_listed(): void
    {
        $this->get('/privacy')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Content')
            ->where('title', 'Privacy Policy')
            ->where('html', fn ($html) => str_contains($html, 'CARTO') && str_contains($html, 'RainViewer')));
    }

    public function test_api_docs_render_the_markdown(): void
    {
        $this->get('/api')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Content')
            ->where('tab', 'API Docs')
            ->where('html', fn ($html) => str_contains($html, 'id="markdown"') && str_contains($html, '<h')));
    }

    public function test_the_meta_description_is_still_server_rendered(): void
    {
        $this->get('/api')->assertSee('<meta name="description" content="Documentation of the API">', false);
    }
}
