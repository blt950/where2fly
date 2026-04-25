<?php

namespace Tests\Feature;

use App\Models\Simulator;
use App\Models\User;
use App\Models\UserList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserListTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Index
    // -------------------------------------------------------------------------

    public function test_list_index_requires_authentication(): void
    {
        $response = $this->get('/lists');

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_list_index(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/lists');

        $response->assertStatus(200);
    }

    // -------------------------------------------------------------------------
    // Create page
    // -------------------------------------------------------------------------

    public function test_list_create_page_requires_authentication(): void
    {
        $response = $this->get('/lists/create');

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_create_list_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/lists/create');

        $response->assertStatus(200);
    }

    // -------------------------------------------------------------------------
    // Store list
    // -------------------------------------------------------------------------

    public function test_user_can_create_a_list(): void
    {
        $user = User::factory()->create();
        $simulator = Simulator::first();

        $response = $this->actingAs($user)->post('/lists/create', [
            'name' => 'My Test List',
            'color' => '#FF5733',
            'simulator' => $simulator->id,
            'airports' => 'KLAX',
        ]);

        $response->assertRedirect(route('list.index'));
        $this->assertDatabaseHas('user_lists', [
            'name' => 'My Test List',
            'user_id' => $user->id,
        ]);
    }

    public function test_user_can_create_a_list_with_airports(): void
    {
        $user = User::factory()->create();
        $simulator = Simulator::first();
        $response = $this->actingAs($user)->post('/lists/create', [
            'name' => 'My Airport List',
            'color' => '#123456',
            'simulator' => $simulator->id,
            'airports' => "KLAX\r\nKSFO",
        ]);

        $response->assertRedirect(route('list.index'));

        $list = UserList::where('name', 'My Airport List')->where('user_id', $user->id)->first();
        $this->assertNotNull($list);
        $this->assertCount(2, $list->airports);
    }

    public function test_creating_list_with_unknown_airport_shows_warning(): void
    {
        $user = User::factory()->create();
        $simulator = Simulator::first();

        $response = $this->actingAs($user)->post('/lists/create', [
            'name' => 'List With Unknown',
            'color' => '#AABBCC',
            'simulator' => $simulator->id,
            'airports' => 'ZZZZ',
        ]);

        $response->assertRedirect(route('list.index'));
        $response->assertSessionHas('warning');
    }

    public function test_list_creation_requires_name(): void
    {
        $user = User::factory()->create();
        $simulator = Simulator::first();

        $response = $this->actingAs($user)->post('/lists/create', [
            'color' => '#FF5733',
            'simulator' => $simulator->id,
            'airports' => 'KLAX',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_list_creation_requires_valid_color(): void
    {
        $user = User::factory()->create();
        $simulator = Simulator::first();

        $response = $this->actingAs($user)->post('/lists/create', [
            'name' => 'Bad Color',
            'color' => 'not-a-color',
            'simulator' => $simulator->id,
            'airports' => 'KLAX',
        ]);

        $response->assertSessionHasErrors('color');
    }

    public function test_list_creation_requires_valid_simulator(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/lists/create', [
            'name' => 'Bad Sim',
            'color' => '#FF5733',
            'simulator' => 99999,
            'airports' => 'KLAX',
        ]);

        $response->assertSessionHasErrors('simulator');
    }

    // -------------------------------------------------------------------------
    // Edit list
    // -------------------------------------------------------------------------

    public function test_user_can_edit_own_list(): void
    {
        $user = User::factory()->create();
        $simulator = Simulator::first();
        $list = UserList::create([
            'name' => 'Original Name',
            'color' => '#111111',
            'simulator_id' => $simulator->id,
            'user_id' => $user->id,
            'public' => false,
        ]);

        $response = $this->actingAs($user)->post("/lists/{$list->id}/edit", [
            'name' => 'Updated Name',
            'color' => '#222222',
            'simulator' => $simulator->id,
            'airports' => 'KLAX',
        ]);

        $response->assertRedirect(route('list.index'));
        $this->assertDatabaseHas('user_lists', [
            'id' => $list->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_user_cannot_edit_another_users_list(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $simulator = Simulator::first();
        $list = UserList::create([
            'name' => 'Owner List',
            'color' => '#111111',
            'simulator_id' => $simulator->id,
            'user_id' => $owner->id,
            'public' => false,
        ]);

        $response = $this->actingAs($other)->post("/lists/{$list->id}/edit", [
            'name' => 'Hijacked Name',
            'color' => '#222222',
            'simulator' => $simulator->id,
            'airports' => 'KLAX',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('user_lists', ['name' => 'Hijacked Name']);
    }

    // -------------------------------------------------------------------------
    // Add / remove airports via update
    // -------------------------------------------------------------------------

    public function test_user_can_add_airports_to_existing_list(): void
    {
        $user = User::factory()->create();
        $simulator = Simulator::first();
        $list = UserList::create([
            'name' => 'Growing List',
            'color' => '#ABCDEF',
            'simulator_id' => $simulator->id,
            'user_id' => $user->id,
            'public' => false,
        ]);

        $response = $this->actingAs($user)->post("/lists/{$list->id}/edit", [
            'name' => 'Growing List',
            'color' => '#ABCDEF',
            'simulator' => $simulator->id,
            'airports' => 'EGLL',
        ]);

        $response->assertRedirect(route('list.index'));
        $list->refresh();
        $this->assertCount(1, $list->airports);
        $this->assertEquals('EGLL', $list->airports->first()->icao);
    }

    public function test_user_can_remove_airports_from_list(): void
    {
        $user = User::factory()->create();
        $simulator = Simulator::first();
        $list = UserList::create([
            'name' => 'Shrinking List',
            'color' => '#FEDCBA',
            'simulator_id' => $simulator->id,
            'user_id' => $user->id,
            'public' => false,
        ]);
        $list->airports()->attach([
            $this->airports['EDDF']->id,
            $this->airports['KLAX']->id,
        ]);

        // Update list with only KLAX, dropping EDDF
        $response = $this->actingAs($user)->post("/lists/{$list->id}/edit", [
            'name' => 'Shrinking List',
            'color' => '#FEDCBA',
            'simulator' => $simulator->id,
            'airports' => 'KLAX',
        ]);

        $response->assertRedirect(route('list.index'));
        $list->refresh();
        $this->assertCount(1, $list->airports);
        $this->assertEquals('KLAX', $list->airports->first()->icao);
    }

    // -------------------------------------------------------------------------
    // Delete list
    // -------------------------------------------------------------------------

    public function test_user_can_delete_own_list(): void
    {
        $user = User::factory()->create();
        $simulator = Simulator::first();
        $list = UserList::create([
            'name' => 'Doomed List',
            'color' => '#000000',
            'simulator_id' => $simulator->id,
            'user_id' => $user->id,
            'public' => false,
        ]);

        $response = $this->actingAs($user)->get("/lists/{$list->id}/delete");

        $response->assertRedirect(route('list.index'));
        $this->assertDatabaseMissing('user_lists', ['id' => $list->id]);
    }

    public function test_user_cannot_delete_another_users_list(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $simulator = Simulator::first();
        $list = UserList::create([
            'name' => 'Protected List',
            'color' => '#FFFFFF',
            'simulator_id' => $simulator->id,
            'user_id' => $owner->id,
            'public' => false,
        ]);

        $response = $this->actingAs($other)->get("/lists/{$list->id}/delete");

        $response->assertForbidden();
        $this->assertDatabaseHas('user_lists', ['id' => $list->id]);
    }

    // -------------------------------------------------------------------------
    // Public flag – only admins may set it
    // -------------------------------------------------------------------------

    public function test_non_admin_cannot_make_list_public(): void
    {
        $user = User::factory()->create(['admin' => false]);
        $simulator = Simulator::first();

        $response = $this->actingAs($user)->post('/lists/create', [
            'name' => 'Public Attempt',
            'color' => '#FF0000',
            'simulator' => $simulator->id,
            'airports' => 'KLAX',
            'public' => true,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('user_lists', ['name' => 'Public Attempt']);
    }

    public function test_admin_can_make_list_public(): void
    {
        $admin = User::factory()->admin()->create();
        $simulator = Simulator::first();

        $response = $this->actingAs($admin)->post('/lists/create', [
            'name' => 'Admin Public List',
            'color' => '#00FF00',
            'simulator' => $simulator->id,
            'airports' => 'KLAX',
            'public' => true,
        ]);

        $response->assertRedirect(route('list.index'));
        $this->assertDatabaseHas('user_lists', [
            'name' => 'Admin Public List',
            'public' => true,
        ]);
    }

    // -------------------------------------------------------------------------
    // Toggle hidden
    // -------------------------------------------------------------------------

    public function test_user_can_toggle_list_visibility(): void
    {
        $user = User::factory()->create();
        $simulator = Simulator::first();
        $list = UserList::create([
            'name' => 'Toggle List',
            'color' => '#123123',
            'simulator_id' => $simulator->id,
            'user_id' => $user->id,
            'public' => false,
            'hidden' => false,
        ]);

        $this->actingAs($user)->get("/lists/{$list->id}/toggle");

        $list->refresh();
        $this->assertTrue((bool) $list->hidden);
    }
}
