<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Admin page access
    // -------------------------------------------------------------------------

    public function test_admin_page_requires_authentication(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_user_cannot_access_admin_page(): void
    {
        $user = User::factory()->create(['admin' => false]);

        $response = $this->actingAs($user)->get('/admin');

        $response->assertForbidden();
    }

    public function test_admin_user_can_access_admin_page(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Admin')
            ->where('stats.users', 1)
            ->where('contributions', []));
    }

    // -------------------------------------------------------------------------
    // UserPolicy reflects admin flag correctly
    // -------------------------------------------------------------------------

    public function test_admin_flag_is_false_for_regular_users(): void
    {
        $user = User::factory()->create(['admin' => false]);

        $this->assertFalse((bool) $user->admin);
    }

    public function test_admin_flag_is_true_for_admin_users(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue((bool) $admin->admin);
    }
}
