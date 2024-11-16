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
        // Check if 'utm_source' exists and is equal to 'efbpro'
        if ($request->has('utm_source') && $request->get('utm_source') === 'efbpro') {
            // Set session variable
            session(['efbpro' => true]);
        }

        return $next($request);
    }
}
