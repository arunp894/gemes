<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\RackController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
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
Route::get('/logout', [LoginController::class, 'logout'])
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
    Route::get('/', fn() => redirect()->route('dashboard'));
    Route::get('/dashboard', fn() => view('welcome'))->name('dashboard');

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
    | Suppliers
    |--------------------------------------------------------------------------
    | Permission-gated. `data` and `toggle-status` are registered BEFORE the
    | resource so they win route matching. Resource binding is constrained
    | to a numeric {supplier}.
    */
    Route::prefix('suppliers')->name('suppliers.')->group(function () {
        Route::get('/data', [SupplierController::class, 'data'])
            ->middleware('permission:suppliers.view')
            ->name('data');

        Route::patch('/{supplier}/toggle-status', [SupplierController::class, 'toggleStatus'])
            ->whereNumber('supplier')
            ->middleware('permission:suppliers.edit')
            ->name('toggle-status');
    });

    Route::resource('suppliers', SupplierController::class)
        ->whereNumber('supplier')
        ->middleware([
            'index'   => 'permission:suppliers.view',
            'show'    => 'permission:suppliers.view',
            'create'  => 'permission:suppliers.create',
            'store'   => 'permission:suppliers.create',
            'edit'    => 'permission:suppliers.edit',
            'update'  => 'permission:suppliers.edit',
            'destroy' => 'permission:suppliers.delete',
        ]);

    /*
    |--------------------------------------------------------------------------
    | Racks (warehouse storage bins)
    |--------------------------------------------------------------------------
    */
    Route::prefix('racks')->name('racks.')->group(function () {
        Route::get('/data', [RackController::class, 'data'])
            ->middleware('permission:racks.view')
            ->name('data');

        Route::patch('/{rack}/toggle-status', [RackController::class, 'toggleStatus'])
            ->whereNumber('rack')
            ->middleware('permission:racks.edit')
            ->name('toggle-status');
    });

    Route::resource('racks', RackController::class)
        ->whereNumber('rack')
        ->middleware([
            'index'   => 'permission:racks.view',
            'show'    => 'permission:racks.view',
            'create'  => 'permission:racks.create',
            'store'   => 'permission:racks.create',
            'edit'    => 'permission:racks.edit',
            'update'  => 'permission:racks.edit',
            'destroy' => 'permission:racks.delete',
        ]);

    /*
    |--------------------------------------------------------------------------
    | Purchases
    |--------------------------------------------------------------------------
    | Non-resourceful endpoints (data, lookups, status transitions) are
    | registered first so they win route matching against the resource.
    */
    Route::prefix('purchases')->name('purchases.')->group(function () {
        Route::get('/data', [PurchaseController::class, 'data'])
            ->middleware('permission:purchases.view')
            ->name('data');

        Route::get('/lookup-barcode', [PurchaseController::class, 'lookupByBarcode'])
            ->middleware('permission:purchases.create')
            ->name('lookup-barcode');

        Route::get('/search-products', [PurchaseController::class, 'searchProducts'])
            ->middleware('permission:purchases.create')
            ->name('search-products');

        Route::get('/preview-invoice-number', [PurchaseController::class, 'previewInvoiceNumber'])
            ->middleware('permission:purchases.create')
            ->name('preview-invoice-number');

        Route::patch('/{purchase}/post', [PurchaseController::class, 'post'])
            ->whereNumber('purchase')
            ->middleware('permission:purchases.post')
            ->name('post');

        Route::patch('/{purchase}/cancel', [PurchaseController::class, 'cancel'])
            ->whereNumber('purchase')
            ->middleware('permission:purchases.edit')
            ->name('cancel');
    });

    Route::resource('purchases', PurchaseController::class)
        ->whereNumber('purchase')
        ->middleware([
            'index'   => 'permission:purchases.view',
            'show'    => 'permission:purchases.view',
            'create'  => 'permission:purchases.create',
            'store'   => 'permission:purchases.create',
            'edit'    => 'permission:purchases.edit',
            'update'  => 'permission:purchases.edit',
            'destroy' => 'permission:purchases.delete',
        ]);

    /*
    |--------------------------------------------------------------------------
    | Users admin (RBAC)
    |--------------------------------------------------------------------------
    | DataTables endpoint + toggle-status registered BEFORE the resource so
    | they win route matching. Resource is constrained to numeric {user}.
    */
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/data', [UserController::class, 'data'])
            ->middleware('permission:users.view')
            ->name('data');

        Route::patch('/{user}/toggle-status', [UserController::class, 'toggleStatus'])
            ->whereNumber('user')
            ->middleware('permission:users.edit')
            ->name('toggle-status');
    });

    Route::resource('users', UserController::class)
        ->whereNumber('user')
        ->middleware([
            'index'   => 'permission:users.view',
            'show'    => 'permission:users.view',
            'create'  => 'permission:users.create',
            'store'   => 'permission:users.create',
            'edit'    => 'permission:users.edit',
            'update'  => 'permission:users.edit',
            'destroy' => 'permission:users.delete',
        ]);

    /*
    |--------------------------------------------------------------------------
    | Roles admin (RBAC)
    |--------------------------------------------------------------------------
    */
    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/data', [RoleController::class, 'data'])
            ->middleware('permission:roles.view')
            ->name('data');
    });

    Route::resource('roles', RoleController::class)
        ->whereNumber('role')
        ->middleware([
            'index'   => 'permission:roles.view',
            'show'    => 'permission:roles.view',
            'create'  => 'permission:roles.create',
            'store'   => 'permission:roles.create',
            'edit'    => 'permission:roles.edit',
            'update'  => 'permission:roles.edit',
            'destroy' => 'permission:roles.delete',
        ]);

    /*
    |--------------------------------------------------------------------------
    | Permissions admin (RBAC) — admin-only by default
    |--------------------------------------------------------------------------
    | Permission management is power-user territory; gated to the admin role
    | rather than a granular permission so it's never accidentally granted.
    */
    Route::prefix('permissions')->name('permissions.')->group(function () {
        Route::get('/data', [PermissionController::class, 'data'])
            ->middleware('role:admin')
            ->name('data');
    });

    Route::resource('permissions', PermissionController::class)
        ->whereNumber('permission')
        ->middleware('role:admin');
});
