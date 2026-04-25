<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UserAccountTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Fake the Cloudflare Turnstile siteverify endpoint so registration and
     * login validators do not make real HTTP calls during tests.
     */
    private function fakeTurnstile(): void
    {
        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
                'success' => true,
                'error-codes' => [],
            ], 200),
        ]);
    }

    // -------------------------------------------------------------------------
    // Registration page
    // -------------------------------------------------------------------------

    public function test_registration_page_loads(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    // -------------------------------------------------------------------------
    // User registration
    // -------------------------------------------------------------------------

    public function test_user_can_register(): void
    {
        $this->fakeTurnstile();

        $response = $this->post('/register', [
            'username' => 'testuser',
            'email' => 'testuser@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'cf-turnstile-response' => 'DUMMY',
            'privacy_policy' => '1',
        ]);

        $response->assertRedirect(route('front'));
        $this->assertDatabaseHas('users', ['username' => 'testuser']);
    }

    public function test_registration_fails_with_short_password(): void
    {
        $this->fakeTurnstile();

        $response = $this->post('/register', [
            'username' => 'testuser',
            'email' => 'testuser@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
            'cf-turnstile-response' => 'DUMMY',
            'privacy_policy' => '1',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['username' => 'testuser']);
    }

    public function test_registration_fails_with_mismatched_passwords(): void
    {
        $this->fakeTurnstile();

        $response = $this->post('/register', [
            'username' => 'testuser',
            'email' => 'testuser@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'DifferentPassword!',
            'cf-turnstile-response' => 'DUMMY',
            'privacy_policy' => '1',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['username' => 'testuser']);
    }

    public function test_registration_fails_with_duplicate_username(): void
    {
        $this->fakeTurnstile();

        User::factory()->create(['username' => 'existinguser']);

        $response = $this->post('/register', [
            'username' => 'existinguser',
            'email' => 'new@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'cf-turnstile-response' => 'DUMMY',
            'privacy_policy' => '1',
        ]);

        $response->assertSessionHasErrors('username');
    }

    public function test_registration_fails_with_duplicate_email(): void
    {
        $this->fakeTurnstile();

        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->post('/register', [
            'username' => 'brandnewuser',
            'email' => 'taken@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'cf-turnstile-response' => 'DUMMY',
            'privacy_policy' => '1',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_registration_fails_without_privacy_policy_acceptance(): void
    {
        $this->fakeTurnstile();

        $response = $this->post('/register', [
            'username' => 'testuser',
            'email' => 'testuser@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'cf-turnstile-response' => 'DUMMY',
            // privacy_policy intentionally omitted
        ]);

        $response->assertSessionHasErrors('privacy_policy');
    }

    public function test_registration_is_blocked_for_authenticated_users(): void
    {
        $this->fakeTurnstile();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/register', [
            'username' => 'anotheruser',
            'email' => 'another@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'cf-turnstile-response' => 'DUMMY',
            'privacy_policy' => '1',
        ]);

        // The route has 'guest' middleware, so authenticated users are redirected
        $response->assertRedirect();
        $this->assertDatabaseMissing('users', ['username' => 'anotheruser']);
    }

    // -------------------------------------------------------------------------
    // Login page
    // -------------------------------------------------------------------------

    public function test_login_page_loads(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    // -------------------------------------------------------------------------
    // Login
    // -------------------------------------------------------------------------

    public function test_user_can_login(): void
    {
        $this->fakeTurnstile();

        $user = User::factory()->create([
            'username' => 'loginuser',
            'password' => bcrypt('Password123!'),
        ]);

        $response = $this->post('/login', [
            'username' => 'loginuser',
            'password' => 'Password123!',
            'cf-turnstile-response' => 'DUMMY',
        ]);

        $response->assertRedirect(route('front'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_login_with_email(): void
    {
        $this->fakeTurnstile();

        $user = User::factory()->create([
            'email' => 'emaillogin@example.com',
            'password' => bcrypt('Password123!'),
        ]);

        $response = $this->post('/login', [
            'username' => 'emaillogin@example.com',
            'password' => 'Password123!',
            'cf-turnstile-response' => 'DUMMY',
        ]);

        $response->assertRedirect(route('front'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $this->fakeTurnstile();

        User::factory()->create([
            'username' => 'loginuser',
            'password' => bcrypt('CorrectPassword!'),
        ]);

        $response = $this->post('/login', [
            'username' => 'loginuser',
            'password' => 'WrongPassword!',
            'cf-turnstile-response' => 'DUMMY',
        ]);

        $response->assertSessionHas('error');
        $this->assertGuest();
    }

    public function test_login_fails_with_nonexistent_username(): void
    {
        $this->fakeTurnstile();

        $response = $this->post('/login', [
            'username' => 'nobody',
            'password' => 'Password123!',
            'cf-turnstile-response' => 'DUMMY',
        ]);

        $response->assertSessionHas('error');
        $this->assertGuest();
    }

    // -------------------------------------------------------------------------
    // Logout
    // -------------------------------------------------------------------------

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/logout');

        $response->assertRedirect(route('front'));
        $this->assertGuest();
    }

    // -------------------------------------------------------------------------
    // Account deletion
    // -------------------------------------------------------------------------

    public function test_user_can_delete_own_account(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/account/delete');

        $response->assertRedirect(route('front'));
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_guest_cannot_access_delete_account_route(): void
    {
        $response = $this->post('/account/delete');

        // Unauthenticated users are redirected to login
        $response->assertRedirect();
    }

    // -------------------------------------------------------------------------
    // Account settings page
    // -------------------------------------------------------------------------

    public function test_account_settings_requires_auth(): void
    {
        $response = $this->get('/account');

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_account_settings(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withSession(['url.intended' => null])
            ->get('/account');

        $response->assertStatus(200);
    }
}
