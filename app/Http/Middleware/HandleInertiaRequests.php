<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Inertia\Inertia;
use Inertia\Middleware;
use Inertia\Support\Header;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'inertia';

    /**
     * A visit from the shell can end on a page that is still Blade, e.g. a failed search redirecting
     * to the form. The client can't render that, so turn it into a full page load, and reflash so
     * the errors and old input survive the extra request.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = parent::handle($request, $next);

        if ($request->header(Header::INERTIA) && $response->isSuccessful() && ! $response->headers->has(Header::INERTIA)) {
            $request->session()->reflash();

            return Inertia::location($request->fullUrl());
        }

        return $response;
    }

    /**
     * The page URL exactly as requested. The default, fullUrl(), sorts the query and re-indexes
     * arrays (destinations[0]=), which would change the shape of every bookmarked search.
     */
    public function urlResolver()
    {
        return fn (Request $request) => $request->getRequestUri();
    }

    /**
     * What layouts/app.blade.php reads from the session, the auth user and the View::share calls
     * of AdminVariables/FeedbackVariables, for the React shell.
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'appVersion' => config('app.version'),
            'isProduction' => app()->isProduction(),
            'auth' => [
                'user' => fn () => ($user = $request->user()) ? [
                    'username' => $user->username,
                    'email' => $user->email,
                    'verified' => $user->hasVerifiedEmail(),
                    'isAdmin' => $user->can('showAdmin', $user::class),
                    'hasReadFeedback' => ! empty($user->feedback_last_read_number),
                    'listsCount' => $user->lists()->count(),
                ] : null,
            ],
            'flash' => fn () => [
                'error' => $request->session()->get('error'),
                'warning' => $request->session()->get('warning'),
                'success' => $request->session()->get('success'),
            ],
            'unreadFeedback' => fn () => View::shared('unreadFeedback'),
            'hasNotifications' => fn () => View::shared('hasNotifications'),
        ];
    }
}
