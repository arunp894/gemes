<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route middleware: check the authenticated user holds at least one
 * of the given permission slugs.
 *
 * Usage in routes:
 *     ->middleware('permission:products.view')
 *     ->middleware('permission:products.create,products.edit')   // OR
 *     ->middleware(['permission:products.view', 'permission:categories.view']) // AND (stacked)
 *
 * Behaviour:
 *   - Unauthenticated -> redirect to login.
 *   - Authenticated super-admin -> always passes.
 *   - Authenticated, none of the listed permissions -> 403.
 *
 * Note: the underlying User::hasAnyPermission() short-circuits on
 * super-admin, so this middleware mirrors that semantics.
 */
class CheckPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        if (empty($permissions)) {
            // No permission specified -> treat as auth-only, allow through.
            return $next($request);
        }

        if ($user->hasAnyPermission($permissions)) {
            return $next($request);
        }

        abort(403, 'You do not have permission to perform this action.');
    }
}
