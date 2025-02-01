<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class AdminVariables
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->admin) {
                // Get the number of scenery->simulators who are not published
                $unpublishedSceneries = \App\Models\Scenery::withPublished(false)->count();
                View::share('hasNotifications', $unpublishedSceneries);
            }

        }

        return $next($request);
    }
}
