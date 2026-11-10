<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(Response::HTTP_UNAUTHORIZED, 'Unauthenticated.');
        }

        if (! $user->is_active) {
            abort(Response::HTTP_FORBIDDEN, 'Your account is deactivated. Please contact support.');
        }

        // Admin role has universal bypass access
        if ($user->role === 'admin') {
            return $next($request);
        }

        if (! in_array($user->role, $roles, true)) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized. You do not have the required role permissions.');
        }

        return $next($request);
    }
}
