<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuthPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_and_register_are_shell_pages_with_the_turnstile_key(): void
    {
        config(['services.turnstile.key' => 'site-key']);

        $this->get('/login')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Login')
            ->where('turnstileKey', 'site-key')
            ->where('nav.login.active', true));

        $this->get('/register')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Register')
            ->where('turnstileKey', 'site-key'));
    }

    public function test_a_failed_registration_refills_the_form_but_never_the_password(): void
    {
        $this->post('/register', ['username' => 'pilot', 'email' => 'not-an-email', 'password' => 'secret123', 'password_confirmation' => 'secret123'])
            ->assertSessionHasErrors('email');

        $this->get('/register')->assertInertia(fn (Assert $page) => $page
            ->where('old.username', 'pilot')
            ->missing('old.password'));
    }

    public function test_account_recovery_is_a_shell_page(): void
    {
        $this->get('/account/recovery')->assertOk()->assertInertia(fn (Assert $page) => $page->component('AccountRecovery'));
    }

    public function test_the_reset_form_gets_its_token_and_email_and_keeps_page_views_on(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->get("/account/recovery/reset/{$token}?email=" . urlencode($user->email))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('PasswordReset')
                ->where('token', $token)
                ->where('email', $user->email))
            // Auto-tracking stays on for the shell session; w2fBeforeSend masks the token
            ->assertDontSee('data-auto-track="false"', false);
    }
}
