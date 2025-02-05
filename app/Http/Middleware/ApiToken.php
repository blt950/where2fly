<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Models\ApiLog;
use Closure;
use Illuminate\Http\Request;

class ApiToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  mixed  $editRights
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $args = '')
    {

        // Allow top endpoint to be accessed without token
        if ($request->isMethod('GET') && $request->is('api/top')) {
            return $next($request);
        }

        // Authenticate by searching for the key, check if middleware requires edit rights and compare to key access
        $key = ApiKey::where('key', $request->bearerToken())->first();

        if ($key == null || ($request->getClientIp() != $key->ip_address && $key->ip_address != '*')) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        if ($key->disabled == true) {
            return response()->json([
                'message' => 'Token temporarily suspended',
            ], 403);
        }

        // Update last used
        $key->update(['last_used_at' => now()]);

        ApiLog::create([
            'api_key_id' => $key->id,
        ]);

        return $next($request);
    }
}
