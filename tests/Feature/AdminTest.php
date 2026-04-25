<?php

namespace Tests\Feature;

use App\Models\Simulator;
use App\Models\User;
use App\Models\UserList;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertStatus(200);
    }

    // -------------------------------------------------------------------------
    // Making a list public (UserListPolicy::public)
    // -------------------------------------------------------------------------

    public function test_non_admin_cannot_make_list_public_when_creating(): void
    {
        $user = User::factory()->create(['admin' => false]);
        $simulator = Simulator::first();

        $response = $this->actingAs($user)->post('/lists/create', [
            'name' => 'Sneaky Public List',
            'color' => '#AAAAAA',
            'simulator' => $simulator->id,
            'airports' => 'KLAX',
            'public' => true,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('user_lists', ['name' => 'Sneaky Public List']);
    }

    public function test_non_admin_cannot_make_list_public_when_editing(): void
    {
        $user = User::factory()->create(['admin' => false]);
        $simulator = Simulator::first();
        $list = UserList::create([
            'name' => 'Private List',
            'color' => '#BBBBBB',
            'simulator_id' => $simulator->id,
            'user_id' => $user->id,
            'public' => false,
        ]);

        $response = $this->actingAs($user)->post("/lists/{$list->id}/edit", [
            'name' => 'Private List',
            'color' => '#BBBBBB',
            'simulator' => $simulator->id,
            'airports' => 'KLAX',
            'public' => true,
        ]);

        $response->assertForbidden();
        $list->refresh();
        $this->assertFalse((bool) $list->public);
    }

    public function test_admin_can_make_list_public_when_editing(): void
    {
        $admin = User::factory()->admin()->create();
        $simulator = Simulator::first();
        $list = UserList::create([
            'name' => 'To Be Public',
            'color' => '#CCCCCC',
            'simulator_id' => $simulator->id,
            'user_id' => $admin->id,
            'public' => false,
        ]);

        $response = $this->actingAs($admin)->post("/lists/{$list->id}/edit", [
            'name' => 'To Be Public',
            'color' => '#CCCCCC',
            'simulator' => $simulator->id,
            'airports' => 'KLAX',
            'public' => true,
        ]);

        $response->assertRedirect(route('list.index'));
        $list->refresh();
        $this->assertTrue((bool) $list->public);
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
