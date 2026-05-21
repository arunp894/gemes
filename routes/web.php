<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guest routes
|--------------------------------------------------------------------------
| Login form + form submit. The `guest` middleware bounces an already
| authenticated user back to the dashboard instead of re-showing the form.
*/
Route::middleware('guest')->group(function () {
    Route::get('/login',  [LoginController::class, 'showLogin'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

/*
|--------------------------------------------------------------------------
| Logout
|--------------------------------------------------------------------------
| POST only (no GET) so CSRF is enforced and crawlers can't trigger it.
*/
Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Authenticated app
|--------------------------------------------------------------------------
| Everything below requires a live session. `/` redirects either to the
| dashboard (authed) or to the login screen (guest, via the `auth` guard).
*/
Route::middleware('auth')->group(function () {

    // Root + dashboard. `/` and `/dashboard` both land on the same view.
    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('/dashboard', fn () => view('welcome'))->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Categories
    |--------------------------------------------------------------------------
    | Permission-gated. `data` and `toggle-status` are registered BEFORE the
    | resource so they win route matching. The resource is constrained to a
    | numeric {category} as a safeguard against literal-path collisions.
    */
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/data', [CategoryController::class, 'data'])
            ->middleware('permission:categories.view')
            ->name('data');

        Route::patch('/{category}/toggle-status', [CategoryController::class, 'toggleStatus'])
            ->whereNumber('category')
            ->middleware('permission:categories.edit')
            ->name('toggle-status');
    });

    Route::resource('categories', CategoryController::class)
        ->whereNumber('category')
        ->middleware([
            'index'   => 'permission:categories.view',
            'show'    => 'permission:categories.view',
            'create'  => 'permission:categories.create',
            'store'   => 'permission:categories.create',
            'edit'    => 'permission:categories.edit',
            'update'  => 'permission:categories.edit',
            'destroy' => 'permission:categories.delete',
        ]);

    /*
    |--------------------------------------------------------------------------
    | Products
    |--------------------------------------------------------------------------
    | Non-resourceful routes registered BEFORE the resource so they win route
    | matching. The resource binding is restricted to a numeric {product}.
    */
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/data', [ProductController::class, 'data'])
            ->middleware('permission:products.view')
            ->name('data');

        Route::get('/subcategories/{category}', [ProductController::class, 'subcategoriesByParent'])
            ->whereNumber('category')
            ->middleware('permission:products.view')
            ->name('subcategories');

        Route::post('/barcodes/generate', [ProductController::class, 'generateBarcode'])
            ->middleware('permission:products.create,products.edit')
            ->name('barcodes.generate');

        Route::post('/barcodes/validate', [ProductController::class, 'validateBarcode'])
            ->middleware('permission:products.create,products.edit')
            ->name('barcodes.validate');

        Route::post('/bulk-website-toggle', [ProductController::class, 'bulkWebsiteToggle'])
            ->middleware('permission:products.toggle-website')
            ->name('bulk-website-toggle');

        Route::patch('/{product}/toggle-status', [ProductController::class, 'toggleStatus'])
            ->whereNumber('product')
            ->middleware('permission:products.edit')
            ->name('toggle-status');

        Route::patch('/{product}/toggle-website', [ProductController::class, 'toggleWebsite'])
            ->whereNumber('product')
            ->middleware('permission:products.toggle-website')
            ->name('toggle-website');
    });

    Route::resource('products', ProductController::class)
        ->whereNumber('product')
        ->middleware([
            'index'   => 'permission:products.view',
            'show'    => 'permission:products.view',
            'create'  => 'permission:products.create',
            'store'   => 'permission:products.create',
            'edit'    => 'permission:products.edit',
            'update'  => 'permission:products.edit',
            'destroy' => 'permission:products.delete',
        ]);

    /*
    |--------------------------------------------------------------------------
    | Roles & Permissions admin (role-gated)
    |--------------------------------------------------------------------------
    | Reserved here so a future RoleController slots in without rewiring auth.
    | Until controllers exist these routes will 404 — that's intentional.
    |
    | Route::resource('roles', RoleController::class)
    |     ->middleware('role:admin');
    | Route::resource('users', UserController::class)
    |     ->middleware('role:admin');
    */
});
