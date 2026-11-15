<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use RuntimeException;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // CSRF checks are skipped in tests, so the expired session is thrown by hand
        Route::middleware('web')->group(function () {
            Route::post('/_test/expired', fn () => throw new TokenMismatchException);
            Route::get('/_test/crash', fn () => throw new RuntimeException('boom'));
            Route::get('/_test/forbidden', fn () => abort(403));
        });
    }

    public function test_an_unknown_url_is_the_sites_404_inside_the_shell(): void
    {
        $this->get('/no-such-page')->assertNotFound()->assertInertia(fn (Assert $page) => $page->component('Error')
            ->where('status', 404)
            ->has('nav.main'));
    }

    public function test_the_404_knows_who_is_viewing(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/no-such-page')->assertInertia(fn (Assert $page) => $page
            ->where('nav.account.label', $user->username));
    }

    public function test_a_shell_visit_to_an_unknown_url_gets_the_page_not_html(): void
    {
        $version = app(HandleInertiaRequests::class)->version(request());

        $this->get('/no-such-page', ['X-Inertia' => 'true', 'X-Inertia-Version' => $version, 'X-Requested-With' => 'XMLHttpRequest'])
            ->assertNotFound()
            ->assertHeader('X-Inertia', 'true')
            ->assertJsonPath('component', 'Error');
    }

    public function test_missing_files_and_json_callers_keep_the_plain_404(): void
    {
        $this->get('/img/missing.png')->assertNotFound()->assertDontSee('<script data-page="app"', false);
        $this->getJson('/no-such-page')->assertNotFound()->assertJsonStructure(['message']);
    }

    public function test_forbidden_is_shown_in_the_shell(): void
    {
        $this->get('/_test/forbidden')->assertForbidden()->assertInertia(fn (Assert $page) => $page->component('Error')->where('status', 403));
    }

    public function test_a_crash_is_shown_in_the_shell_unless_debugging(): void
    {
        config(['app.debug' => false]);
        $this->get('/_test/crash')->assertServerError()->assertInertia(fn (Assert $page) => $page->component('Error')->where('status', 500));

        config(['app.debug' => true]);
        $this->get('/_test/crash')->assertServerError()->assertDontSee('<script data-page="app"', false);
    }

    public function test_a_crash_while_rendering_the_error_page_falls_back_to_the_plain_view(): void
    {
        config(['app.debug' => false]);
        // As in a database outage: the shared props query it, and throw again on the error page
        $this->app->bind(HandleInertiaRequests::class, fn () => new class extends HandleInertiaRequests
        {
            public function share(Request $request): array
            {
                throw new RuntimeException('database down');
            }
        });

        $this->get('/changelog')->assertServerError()
            ->assertDontSee('<script data-page="app"', false)
            ->assertSee('<title>Server Error</title>', false);
    }

    public function test_an_expired_session_goes_back_with_the_input_and_an_explanation(): void
    {
        $this->post('/_test/expired', ['message' => 'Typed text', 'password' => 'secret'], ['Referer' => url('/feedback')])
            ->assertRedirect('/feedback')
            ->assertSessionHas('error')
            ->assertSessionHasInput('message', 'Typed text')
            ->assertSessionMissing('_old_input.password');
    }

    public function test_json_callers_still_get_a_419(): void
    {
        $this->postJson('/_test/expired')->assertStatus(419);
    }

    public function test_the_shell_gets_the_current_csrf_token(): void
    {
        $this->get('/changelog')->assertInertia(fn (Assert $page) => $page->where('csrfToken', csrf_token()));
    }
}
