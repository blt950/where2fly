<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckUtmSource
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if 'utm_source' exists and is equal to 'efbX'
        if ($request->has('utm_source') && $request->get('utm_source') === 'efbX') {
            // Persist session variable for the whole session
            $request->session()->put('efbX', true);
        }

        return $next($request);
    }
}
