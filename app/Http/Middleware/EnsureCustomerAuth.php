<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guard storefront routes that require a logged-in customer.
 *
 * If the customer is not authenticated via the 'customer' guard,
 * redirect to the customer login page with the intended URL stored
 * in session so post-login redirect works automatically.
 */
class EnsureCustomerAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth('customer')->check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Please log in to continue.'], 401);
            }

            session()->put('url.customer_intended', $request->url());

            return redirect()->route('website.auth.login')
                ->with('info', 'Please log in to continue.');
        }

        return $next($request);
    }
}
