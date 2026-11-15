<?php

use App\Http\Middleware\AdminVariables;
use App\Http\Middleware\ApiToken;
use App\Http\Middleware\FeedbackVariables;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\UserActive;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\ExceptionResponse;
use Inertia\Inertia;
use Sentry\State\Scope;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(
            at: [
                '173.245.48.0/20', // Cloudflare as of 2025-04-20
                '103.21.244.0/22',
                '103.22.200.0/22',
                '103.31.4.0/22',
                '141.101.64.0/18',
                '108.162.192.0/18',
                '190.93.240.0/20',
                '188.114.96.0/20',
                '197.234.240.0/22',
                '198.41.128.0/17',
                '162.158.0.0/15',
                '104.16.0.0/13',
                '104.24.0.0/14',
                '172.64.0.0/13',
                '131.0.72.0/22',
                '2400:cb00::/32',
                '2606:4700::/32',
                '2803:f800::/32',
                '2405:b500::/32',
                '2405:8100::/32',
                '2a06:98c0::/29',
                '2c0f:f248::/32',
                '172.16.0.0/12', // Docker
            ],
            headers: Request::HEADER_X_FORWARDED_FOR |
                     Request::HEADER_X_FORWARDED_HOST |
                     Request::HEADER_X_FORWARDED_PORT |
                     Request::HEADER_X_FORWARDED_PROTO |
                     Request::HEADER_X_FORWARDED_AWS_ELB,
        );

        $middleware->trimStrings(except: [
            'current_password',
            'password',
            'password_confirmation',
        ]);

        $middleware->web(append: [
            UserActive::class,
            AdminVariables::class,
            FeedbackVariables::class,
            HandleInertiaRequests::class,
        ]);

        $middleware->statefulApi();
        $middleware->throttleApi('50,1');

        $middleware->alias([
            'api-token' => ApiToken::class,
        ]);

        $middleware->redirectGuestsTo(fn (Request $request) => route('login'));
        $middleware->redirectUsersTo(fn () => route('front'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->reportable(function (Throwable $e) {
            if (app()->bound('sentry')) {
                app('sentry')->configureScope(function (Scope $scope): void {
                    $scope->setUser(['id' => Auth::id()]);
                });
                app('sentry')->captureException($e);
            }
        });

        // Errors look like the site, in the shell (pages/Error.jsx); JSON callers keep their JSON, and
        // debug mode keeps Laravel's page for crashes. Without a session (maintenance) the Blade views stay.
        Inertia::handleExceptionsUsing(function (ExceptionResponse $response) {
            $request = $response->request;
            $status = $response->statusCode();

            if ($request->expectsJson() && ! $request->inertia()) {
                return null;
            }

            // An expired session: back to the form with what was typed, not a dead end
            if ($status === 419) {
                return back()
                    ->withInput($request->except(['_token', 'password', 'password_confirmation', 'current_password']))
                    ->with('error', 'Your session had expired, so that didn\'t go through. Please try again.');
            }

            $shown = in_array($status, [403, 404], true) || (in_array($status, [500, 503], true) && ! config('app.debug'));

            return $shown && $request->hasSession()
                ? $response->render('Error', ['status' => $status])->withSharedData()
                : null;
        });
    })
    ->withCommands()
    ->create();
