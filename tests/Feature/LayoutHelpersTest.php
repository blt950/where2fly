<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LayoutHelpersTest extends TestCase
{
    use RefreshDatabase;

    public function test_flash_messages_reach_the_shell_and_the_simple_blade_pages_escaped(): void
    {
        $text = 'List created, however these airports could not be found: <b>XX</b>';

        $this->withSession(['warning' => $text])->get('/top')->assertInertia(fn (Assert $page) => $page
            ->where('alerts.messages.0', ['key' => 'warning', 'variant' => 'warning', 'text' => $text])
            ->where('alerts.verification', null));

        // The simple Blade layout (admin, scenery create/edit) shows the same messages, escaped too
        $this->actingAs(User::factory()->create())->withSession(['warning' => $text])->get('/scenery/create')
            ->assertSee(e($text), false)
            ->assertDontSee('<b>XX</b>', false);
    }

    public function test_an_unverified_user_gets_the_verification_notice_unless_a_success_shows(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get('/top')->assertInertia(fn (Assert $page) => $page
            ->where('alerts.verification.email', $user->email));

        $this->actingAs($user)->withSession(['success' => 'Done'])->get('/top')->assertInertia(fn (Assert $page) => $page
            ->where('alerts.verification', null));
    }

    public function test_the_map_hint_follows_the_user_and_the_feedback_hint_is_gone(): void
    {
        $this->get('/scenery')->assertInertia(fn (Assert $page) => $page
            ->where('mapHint.text', 'Create an account to fill the map with your own scenery list.'));

        $user = User::factory()->create(['feedback_last_read_number' => null]);
        $this->actingAs($user)->get('/scenery')->assertInertia(fn (Assert $page) => $page
            ->where('mapHint.text', 'Create a scenery list to fill the map with your own data.'));

        UserList::create(['name' => 'Mine', 'user_id' => $user->id, 'public' => false, 'color' => '#fff']);
        $this->actingAs($user)->get('/scenery')->assertInertia(fn (Assert $page) => $page->where('mapHint', null));
    }
}
