<?php

use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\EnsureCustomerAuth;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        /*
         | Route middleware aliases.
         |
         | role:           ->middleware('role:admin,manager')
         | permission:     ->middleware('permission:products.view')
         | customer.auth:  ->middleware('customer.auth')  ← storefront guard
         */
        $middleware->alias([
            'role'          => CheckRole::class,
            'permission'    => CheckPermission::class,
            'customer.auth' => EnsureCustomerAuth::class,
        ]);

        /*
         | Redirect unauthenticated back-office users to the named `login` route.
         | Storefront customer redirects are handled by EnsureCustomerAuth.
         */
        $middleware->redirectGuestsTo(fn () => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
