<?php

namespace App\Http\Middleware;

use App\Models\Scenery;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class AdminVariables
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response|RedirectResponse)  $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {

        if (Auth::check()) {
            $user = Auth::user();
            if ($user->admin) {
                // Get the number of scenery->simulators who are not published
                $unpublishedSceneries = Scenery::where('published', false)->count();
                View::share('hasNotifications', $unpublishedSceneries);
            }

        }

        return $next($request);
    }
}
