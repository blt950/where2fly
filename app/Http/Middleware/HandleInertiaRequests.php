<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'inertia';

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
