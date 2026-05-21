<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route middleware: check the authenticated user holds one of the
 * given role slugs.
 *
 * Usage in routes:
 *     ->middleware('role:admin')
 *     ->middleware('role:admin,manager')      // OR-semantics: admin OR manager
 *
 * Behaviour:
 *   - Unauthenticated   -> redirect to login.
 *   - Authenticated but role missing -> 403.
 */
class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        // Super users bypass role gating as well.
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        if (empty($roles) || $user->hasAnyRole($roles)) {
            return $next($request);
        }

        abort(403, 'You do not have the required role to access this resource.');
    }
}
