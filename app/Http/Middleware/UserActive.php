<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response|RedirectResponse)  $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check()) {
            $user = auth()->user();

            if ($user->last_activity_at === null || $user->last_activity_at->lt(now()->subMinutes(5))) {
                $user->timestamps = false;
                $user->last_activity_at = now();
                $user->save();
            }
        }

        return $next($request);
    }
}
