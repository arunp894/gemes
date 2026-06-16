<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\CustomerAccountController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\RackController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SaleImportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WebsiteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Storefront — Sukaina Gems Website
|--------------------------------------------------------------------------
*/

Route::prefix('store')->name('website.')->group(function () {

    Route::get('/',                   [WebsiteController::class, 'home'])->name('home');
    Route::get('/collections',        [WebsiteController::class, 'collections'])->name('collections');
    Route::get('/products/{product}', [WebsiteController::class, 'product'])->name('product')->whereNumber('product');

    Route::prefix('cart')->name('cart.')->group(function () {
        Route::get('/',             [CartController::class, 'index'])->name('index');
        Route::get('/data',         [CartController::class, 'data'])->name('data');
        Route::post('/add',         [CartController::class, 'add'])->name('add');
        Route::post('/remove',      [CartController::class, 'remove'])->name('remove');
        Route::post('/update-qty',  [CartController::class, 'updateQty'])->name('update-qty');
        Route::post('/clear',       [CartController::class, 'clear'])->name('clear');
        Route::get('/count',        [CartController::class, 'count'])->name('count');
    });

    Route::prefix('checkout')->name('checkout.')->group(function () {
        Route::get('/',          [CheckoutController::class, 'index'])->name('index');
        Route::post('/create',   [CheckoutController::class, 'createOrder'])->name('create');
        Route::post('/capture',  [CheckoutController::class, 'captureOrder'])->name('capture');
        Route::get('/success',   [CheckoutController::class, 'success'])->name('success');
    });

    Route::prefix('auth')->name('auth.')->group(function () {
        Route::get('/login',     [CustomerAuthController::class, 'showLogin'])->name('login');
        Route::post('/login',    [CustomerAuthController::class, 'login'])->name('login.post');
        Route::get('/register',  [CustomerAuthController::class, 'showRegister'])->name('register');
        Route::post('/register', [CustomerAuthController::class, 'register'])->name('register.post');
        Route::post('/logout',   [CustomerAuthController::class, 'logout'])
            ->middleware('customer.auth')
            ->name('logout');
    });

    Route::prefix('account')->name('account.')
        ->middleware('customer.auth')
        ->group(function () {
            Route::get('/',                  [CustomerAccountController::class, 'profile'])->name('profile');
            Route::get('/edit',              [CustomerAccountController::class, 'editProfile'])->name('edit');
            Route::patch('/update',          [CustomerAccountController::class, 'updateProfile'])->name('update');
            Route::get('/orders',            [CustomerAccountController::class, 'orders'])->name('orders');
            Route::get('/orders/{sale}',     [CustomerAccountController::class, 'orderDetail'])->name('order-detail')->whereNumber('sale');
            Route::patch('/change-password', [CustomerAccountController::class, 'changePassword'])->name('change-password');
        });
});

/*
|--------------------------------------------------------------------------
| Guest routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login',  [LoginController::class, 'showLogin'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::get('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Authenticated app
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    Route::get('/', fn() => redirect()->route('dashboard'));
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('role:admin')->prefix('settings')->name('settings.')->group(function () {
        Route::get('/',             [SettingController::class, 'index'])->name('index');
        Route::post('/save',        [SettingController::class, 'save'])->name('save');
        Route::post('/paypal-test', [SettingController::class, 'testPaypal'])->name('paypal-test');
    });

    /*
    |--------------------------------------------------------------------------
    | Channels
    |--------------------------------------------------------------------------
    */
    Route::prefix('channels')->name('channels.')->group(function () {
        Route::get('/data', [ChannelController::class, 'data'])
            ->middleware('permission:channels.view')->name('data');
        Route::patch('/{channel}/toggle-status', [ChannelController::class, 'toggleStatus'])
            ->whereNumber('channel')->middleware('permission:channels.edit')->name('toggle-status');
    });
    Route::resource('channels', ChannelController::class)->whereNumber('channel')
        ->middleware([
            'index'   => 'permission:channels.view',
            'show'    => 'permission:channels.view',
            'create'  => 'permission:channels.create',
            'store'   => 'permission:channels.create',
            'edit'    => 'permission:channels.edit',
            'update'  => 'permission:channels.edit',
            'destroy' => 'permission:channels.delete',
        ]);

    /*
    |--------------------------------------------------------------------------
    | Categories
    |--------------------------------------------------------------------------
    */
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/data', [CategoryController::class, 'data'])
            ->middleware('permission:categories.view')->name('data');
        Route::patch('/{category}/toggle-status', [CategoryController::class, 'toggleStatus'])
            ->whereNumber('category')->middleware('permission:categories.edit')->name('toggle-status');
    });
    Route::resource('categories', CategoryController::class)->whereNumber('category')
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
    */
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/data', [ProductController::class, 'data'])
            ->middleware('permission:products.view')->name('data');
        Route::get('/subcategories/{category}', [ProductController::class, 'subcategoriesByParent'])
            ->whereNumber('category')->middleware('permission:products.view')->name('subcategories');
        Route::post('/barcodes/generate', [ProductController::class, 'generateBarcode'])
            ->middleware('permission:products.create,products.edit')->name('barcodes.generate');
        Route::post('/barcodes/validate', [ProductController::class, 'validateBarcode'])
            ->middleware('permission:products.create,products.edit')->name('barcodes.validate');
        Route::post('/bulk-website-toggle', [ProductController::class, 'bulkWebsiteToggle'])
            ->middleware('permission:products.toggle-website')->name('bulk-website-toggle');
        Route::patch('/{product}/toggle-status', [ProductController::class, 'toggleStatus'])
            ->whereNumber('product')->middleware('permission:products.edit')->name('toggle-status');
        Route::patch('/{product}/toggle-website', [ProductController::class, 'toggleWebsite'])
            ->whereNumber('product')->middleware('permission:products.toggle-website')->name('toggle-website');
    });
    Route::resource('products', ProductController::class)->whereNumber('product')
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
    */
    Route::prefix('suppliers')->name('suppliers.')->group(function () {
        Route::get('/data', [SupplierController::class, 'data'])
            ->middleware('permission:suppliers.view')->name('data');
        Route::get('/{supplier}/purchases-data', [SupplierController::class, 'purchasesData'])
            ->whereNumber('supplier')->middleware('permission:suppliers.view')->name('purchases-data');
        Route::patch('/{supplier}/toggle-status', [SupplierController::class, 'toggleStatus'])
            ->whereNumber('supplier')->middleware('permission:suppliers.edit')->name('toggle-status');
    });
    Route::resource('suppliers', SupplierController::class)->whereNumber('supplier')
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
    | Racks
    |--------------------------------------------------------------------------
    */
    Route::prefix('racks')->name('racks.')->group(function () {
        Route::get('/data', [RackController::class, 'data'])
            ->middleware('permission:racks.view')->name('data');
        Route::patch('/{rack}/toggle-status', [RackController::class, 'toggleStatus'])
            ->whereNumber('rack')->middleware('permission:racks.edit')->name('toggle-status');
    });
    Route::resource('racks', RackController::class)->whereNumber('rack')
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
    | Locations
    |--------------------------------------------------------------------------
    */
    Route::prefix('locations')->name('locations.')->group(function () {
        Route::get('/data', [LocationController::class, 'data'])
            ->middleware('permission:locations.view')->name('data');
        Route::patch('/{location}/toggle-status', [LocationController::class, 'toggleStatus'])
            ->whereNumber('location')->middleware('permission:locations.edit')->name('toggle-status');
        Route::patch('/{location}/set-default', [LocationController::class, 'setDefault'])
            ->whereNumber('location')->middleware('permission:locations.edit')->name('set-default');
    });
    Route::resource('locations', LocationController::class)->whereNumber('location')
        ->middleware([
            'index'   => 'permission:locations.view',
            'show'    => 'permission:locations.view',
            'create'  => 'permission:locations.create',
            'store'   => 'permission:locations.create',
            'edit'    => 'permission:locations.edit',
            'update'  => 'permission:locations.edit',
            'destroy' => 'permission:locations.delete',
        ]);

    /*
    |--------------------------------------------------------------------------
    | Purchases
    |--------------------------------------------------------------------------
    */
    Route::prefix('purchases')->name('purchases.')->group(function () {
        Route::get('/data', [PurchaseController::class, 'data'])
            ->middleware('permission:purchases.view')->name('data');
        Route::get('/lookup-barcode', [PurchaseController::class, 'lookupByBarcode'])
            ->middleware('permission:purchases.create')->name('lookup-barcode');
        Route::get('/search-products', [PurchaseController::class, 'searchProducts'])
            ->middleware('permission:purchases.create')->name('search-products');
        Route::get('/preview-invoice-number', [PurchaseController::class, 'previewInvoiceNumber'])
            ->middleware('permission:purchases.create')->name('preview-invoice-number');
        Route::patch('/{purchase}/post', [PurchaseController::class, 'post'])
            ->whereNumber('purchase')->middleware('permission:purchases.post')->name('post');
        Route::patch('/{purchase}/cancel', [PurchaseController::class, 'cancel'])
            ->whereNumber('purchase')->middleware('permission:purchases.edit')->name('cancel');
    });
    Route::resource('purchases', PurchaseController::class)->whereNumber('purchase')
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
    | Customers
    |--------------------------------------------------------------------------
    */
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/data', [CustomerController::class, 'data'])
            ->middleware('permission:customers.view')->name('data');
        Route::get('/search', [CustomerController::class, 'search'])
            ->middleware('permission:customers.view,sales.create')->name('search');
        Route::patch('/{customer}/toggle-status', [CustomerController::class, 'toggleStatus'])
            ->whereNumber('customer')->middleware('permission:customers.edit')->name('toggle-status');
    });
    Route::resource('customers', CustomerController::class)->whereNumber('customer')
        ->middleware([
            'index'   => 'permission:customers.view',
            'show'    => 'permission:customers.view',
            'create'  => 'permission:customers.create',
            'store'   => 'permission:customers.create',
            'edit'    => 'permission:customers.edit',
            'update'  => 'permission:customers.edit',
            'destroy' => 'permission:customers.delete',
        ]);

    /*
    |--------------------------------------------------------------------------
    | Sales  (import routes BEFORE resource to avoid {sale} wildcard clash)
    |--------------------------------------------------------------------------
    */
    Route::prefix('sales')->name('sales.')->group(function () {

        // ── DataTables / lookup / terminal helpers ──
        Route::get('/data', [SaleController::class, 'data'])
            ->middleware('permission:sales.view')->name('data');
        Route::get('/lookup-barcode', [SaleController::class, 'lookupByBarcode'])
            ->middleware('permission:sales.create')->name('lookup-barcode');
        Route::get('/search-products', [SaleController::class, 'searchProducts'])
            ->middleware('permission:sales.create')->name('search-products');
        Route::get('/preview-number', [SaleController::class, 'previewSaleNumber'])
            ->middleware('permission:sales.create')->name('preview-number');

        // ── Import (registered BEFORE resource so they don't match {sale}) ──
        Route::get('/import',          [SaleImportController::class, 'showUploadForm'])
            ->middleware('permission:sales.import')->name('import');
        Route::post('/import/preview', [SaleImportController::class, 'preview'])
            ->middleware('permission:sales.import')->name('import.preview');
        Route::post('/import/confirm', [SaleImportController::class, 'confirm'])
            ->middleware('permission:sales.import')->name('import.confirm');
        Route::get('/import/template', [SaleImportController::class, 'downloadTemplate'])
            ->middleware('permission:sales.import')->name('import.template');

        // ── Status transitions ──
        Route::post('/{sale}/post',     [SaleController::class, 'post'])
            ->whereNumber('sale')->middleware('permission:sales.post')->name('post');
        Route::post('/{sale}/complete', [SaleController::class, 'complete'])
            ->whereNumber('sale')->middleware('permission:sales.post')->name('complete');
        Route::post('/{sale}/refund',   [SaleController::class, 'refund'])
            ->whereNumber('sale')->middleware('permission:sales.post')->name('refund');
        Route::post('/{sale}/cancel',   [SaleController::class, 'cancel'])
            ->whereNumber('sale')->middleware('permission:sales.edit')->name('cancel');

        // ── Payments ──
        Route::post('/{sale}/payments', [SaleController::class, 'addPayment'])
            ->whereNumber('sale')->middleware('permission:sales.edit')->name('payments.store');
        Route::delete('/{sale}/payments/{payment}', [SaleController::class, 'removePayment'])
            ->whereNumber('sale')->whereNumber('payment')
            ->middleware('permission:sales.edit')->name('payments.destroy');
    });

    Route::resource('sales', SaleController::class)->whereNumber('sale')
        ->middleware([
            'index'   => 'permission:sales.view',
            'show'    => 'permission:sales.view',
            'create'  => 'permission:sales.create',
            'store'   => 'permission:sales.create',
            'edit'    => 'permission:sales.edit',
            'update'  => 'permission:sales.edit',
            'destroy' => 'permission:sales.delete',
        ]);

    /*
    |--------------------------------------------------------------------------
    | Stock
    |--------------------------------------------------------------------------
    */
    Route::prefix('stock')->name('stock.')->group(function () {
        Route::get('/', [StockController::class, 'index'])
            ->middleware('permission:stock.view')->name('index');
        Route::get('/data', [StockController::class, 'data'])
            ->middleware('permission:stock.view')->name('data');
        Route::get('/product/{product}', [StockController::class, 'product'])
            ->whereNumber('product')->middleware('permission:stock.view')->name('product');
        Route::get('/piece/{purchaseProduct}', [StockController::class, 'piece'])
            ->whereNumber('purchaseProduct')->middleware('permission:stock.view')->name('piece');
    });

    /*
    |--------------------------------------------------------------------------
    | Stock Transfers
    |--------------------------------------------------------------------------
    */
    Route::prefix('stock-transfers')->name('stock-transfers.')->group(function () {
        Route::get('/data', [StockTransferController::class, 'data'])
            ->middleware('permission:stock-transfers.view')->name('data');
        Route::get('/lookup-barcode', [StockTransferController::class, 'lookupByBarcode'])
            ->middleware('permission:stock-transfers.create')->name('lookup-barcode');
        Route::post('/{stockTransfer}/post', [StockTransferController::class, 'post'])
            ->whereNumber('stockTransfer')->middleware('permission:stock-transfers.post')->name('post');
        Route::post('/{stockTransfer}/receive', [StockTransferController::class, 'receive'])
            ->whereNumber('stockTransfer')->middleware('permission:stock-transfers.post')->name('receive');
        Route::post('/{stockTransfer}/cancel', [StockTransferController::class, 'cancel'])
            ->whereNumber('stockTransfer')->middleware('permission:stock-transfers.edit')->name('cancel');
    });
    Route::resource('stock-transfers', StockTransferController::class)
        ->whereNumber('stockTransfer')
        ->parameters(['stock-transfers' => 'stockTransfer'])
        ->middleware([
            'index'   => 'permission:stock-transfers.view',
            'show'    => 'permission:stock-transfers.view',
            'create'  => 'permission:stock-transfers.create',
            'store'   => 'permission:stock-transfers.create',
            'edit'    => 'permission:stock-transfers.edit',
            'update'  => 'permission:stock-transfers.edit',
            'destroy' => 'permission:stock-transfers.delete',
        ]);

    /*
    |--------------------------------------------------------------------------
    | Banners
    |--------------------------------------------------------------------------
    */
    Route::prefix('banners')->name('banners.')->group(function () {
        Route::get('/data', [BannerController::class, 'data'])
            ->middleware('permission:banners.view')->name('data');
        Route::patch('/{banner}/toggle-status', [BannerController::class, 'toggleStatus'])
            ->whereNumber('banner')->middleware('permission:banners.edit')->name('toggle-status');
    });
    Route::resource('banners', BannerController::class)->whereNumber('banner')
        ->middleware([
            'index'   => 'permission:banners.view',
            'show'    => 'permission:banners.view',
            'create'  => 'permission:banners.create',
            'store'   => 'permission:banners.create',
            'edit'    => 'permission:banners.edit',
            'update'  => 'permission:banners.edit',
            'destroy' => 'permission:banners.delete',
        ]);

    /*
    |--------------------------------------------------------------------------
    | Users (RBAC)
    |--------------------------------------------------------------------------
    */
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/data', [UserController::class, 'data'])
            ->middleware('permission:users.view')->name('data');
        Route::patch('/{user}/toggle-status', [UserController::class, 'toggleStatus'])
            ->whereNumber('user')->middleware('permission:users.edit')->name('toggle-status');
    });
    Route::resource('users', UserController::class)->whereNumber('user')
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
    | Roles (RBAC)
    |--------------------------------------------------------------------------
    */
    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/data', [RoleController::class, 'data'])
            ->middleware('permission:roles.view')->name('data');
    });
    Route::resource('roles', RoleController::class)->whereNumber('role')
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
    | Permissions (RBAC) — admin only
    |--------------------------------------------------------------------------
    */
    Route::prefix('permissions')->name('permissions.')->group(function () {
        Route::get('/data', [PermissionController::class, 'data'])
            ->middleware('role:admin')->name('data');
    });
    Route::resource('permissions', PermissionController::class)
        ->whereNumber('permission')
        ->middleware('role:admin');
});
