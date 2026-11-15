<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_shell_gets_the_navigation_with_the_current_page_active(): void
    {
        $this->get('/top')->assertInertia(fn (Assert $page) => $page
            ->where('nav.main.1.key', 'top')
            ->where('nav.main.1.active', true)
            ->where('nav.main.0.active', false)
            ->where('nav.main.1.shell', true)
            ->where('nav.feedback.shell', true)
            ->where('nav.login.shell', true)
            ->where('nav.login.key', 'login')
            ->where('nav.account', null)
            ->where('nav.moreActive', false));
    }

    public function test_more_lights_up_on_a_page_behind_it(): void
    {
        $this->get('/changelog')->assertInertia(fn (Assert $page) => $page
            ->where('nav.moreActive', true)
            ->where('nav.info.0.active', true));
    }

    public function test_a_signed_in_user_gets_the_account_items_but_not_admin(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/top')->assertInertia(fn (Assert $page) => $page
            ->where('nav.login', null)
            ->where('nav.account.label', $user->username)
            ->where('nav.account.items', fn ($items) => collect($items)->pluck('key')->all() === ['settings', 'lists', 'logout']));
    }

    public function test_the_simple_blade_pages_have_a_way_back_and_no_second_navigation(): void
    {
        $this->actingAs(User::factory()->create())->get('/scenery/create')
            ->assertOk()
            ->assertSee('Back to the map')
            ->assertDontSee('bottom-nav', false)
            ->assertDontSee('id="map"', false);
    }
}
