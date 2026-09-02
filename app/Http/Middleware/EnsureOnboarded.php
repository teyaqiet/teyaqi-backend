<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboarded
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle($request, Closure $next)
{
    // ⚡ FIX: Allow the user to check their own basic profile data even if not onboarded
    if ($request->is('api/user') || $request->routeIs('user.profile')) {
        return $next($request);
    }

    if ($request->user() && !$request->user()->has_onboarded) {
        return response()->json([
            'status' => 'error',
            'message' => 'Onboarding required.',
            'code' => 'ONBOARDING_REQUIRED'
        ], 403);
    }

    return $next($request);
}


}
