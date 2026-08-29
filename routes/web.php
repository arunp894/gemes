<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BarcodeHistoryController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CustomerAccountController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TodayPerformanceController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\CountryOfOriginController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaypalWebhookController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\RackController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SaleController;
use App\Models\Sale;
use App\Http\Controllers\SaleImportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StockAuditController;
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

Route::name('website.')->group(function () {

    Route::get('/',                   [WebsiteController::class, 'home'])->name('home');
    Route::get('/collections',        [WebsiteController::class, 'collections'])->name('collections');
    Route::get('/products/{product}', [WebsiteController::class, 'product'])->name('product')->whereNumber('product');

    Route::prefix('blog')->name('blog.')->group(function () {
        Route::get('/',        [WebsiteController::class, 'blogIndex'])->name('index');
        Route::get('/{blog:slug}', [WebsiteController::class, 'blogShow'])->name('show');
    });

    Route::get('/pages/{page:slug}', [WebsiteController::class, 'pageShow'])->name('pages.show');

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

    // PayPal calls this directly — no customer session, so it can't sit
    // behind customer.auth. Trust comes from PaypalWebhookController's own
    // signature verification instead. CSRF is exempted for this URI in
    // bootstrap/app.php (PayPal doesn't send a CSRF token either).
    Route::post('/paypal/webhook', [PaypalWebhookController::class, 'handle'])->name('paypal.webhook');

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
| Admin Panel — prefixed /admin
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->group(function () {

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
    Route::get('/reports/today-performance', [TodayPerformanceController::class, 'index'])->name('reports.today-performance');

    Route::middleware('role:admin')->prefix('settings')->name('settings.')->group(function () {
        Route::get('/',             [SettingController::class, 'index'])->name('index');
        Route::post('/save',        [SettingController::class, 'save'])->name('save');
        Route::post('/paypal-test', [SettingController::class, 'testPaypal'])->name('paypal-test');
    });

    /*
    |--------------------------------------------------------------------------
    | Pages (About Us, Terms & Conditions, ...)
    |--------------------------------------------------------------------------
    */
    Route::resource('pages', PageController::class)
        ->except(['show'])
        ->whereNumber('page')
        ->middlewareFor('index', 'permission:pages.view')
        ->middlewareFor(['create', 'store'], 'permission:pages.create')
        ->middlewareFor(['edit', 'update'], 'permission:pages.edit')
        ->middlewareFor('destroy', 'permission:pages.delete');

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
        ->middlewareFor(['index', 'show'], 'permission:channels.view')
        ->middlewareFor(['create', 'store'], 'permission:channels.create')
        ->middlewareFor(['edit', 'update'], 'permission:channels.edit')
        ->middlewareFor('destroy', 'permission:channels.delete');

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
        ->middlewareFor(['index', 'show'], 'permission:categories.view')
        ->middlewareFor(['create', 'store'], 'permission:categories.create')
        ->middlewareFor(['edit', 'update'], 'permission:categories.edit')
        ->middlewareFor('destroy', 'permission:categories.delete');

    /*
    |--------------------------------------------------------------------------
    | Countries of Origin
    |--------------------------------------------------------------------------
    */
    Route::prefix('country-origins')->name('country-origins.')->group(function () {
        Route::get('/data', [CountryOfOriginController::class, 'data'])
            ->middleware('permission:country-origins.view')->name('data');
        Route::patch('/{countryOrigin}/toggle-status', [CountryOfOriginController::class, 'toggleStatus'])
            ->whereNumber('countryOrigin')->middleware('permission:country-origins.edit')->name('toggle-status');
    });
    Route::resource('country-origins', CountryOfOriginController::class)
        ->whereNumber('countryOrigin')
        ->parameters(['country-origins' => 'countryOrigin'])
        ->middlewareFor(['index', 'show'], 'permission:country-origins.view')
        ->middlewareFor(['create', 'store'], 'permission:country-origins.create')
        ->middlewareFor(['edit', 'update'], 'permission:country-origins.edit')
        ->middlewareFor('destroy', 'permission:country-origins.delete');

    /*
    |--------------------------------------------------------------------------
    | Products
    |--------------------------------------------------------------------------
    */
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/data', [ProductController::class, 'data'])
            ->middleware('permission:products.view')->name('data');
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
        ->middlewareFor(['index', 'show'], 'permission:products.view')
        ->middlewareFor(['create', 'store'], 'permission:products.create')
        ->middlewareFor(['edit', 'update'], 'permission:products.edit')
        ->middlewareFor('destroy', 'permission:products.delete');

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
        Route::get('/{supplier}/categories', [SupplierController::class, 'categories'])
            ->whereNumber('supplier')->middleware('permission:suppliers.view,purchases.create')->name('categories');
        Route::patch('/{supplier}/toggle-status', [SupplierController::class, 'toggleStatus'])
            ->whereNumber('supplier')->middleware('permission:suppliers.edit')->name('toggle-status');
    });
    Route::resource('suppliers', SupplierController::class)->whereNumber('supplier')
        ->middlewareFor(['index', 'show'], 'permission:suppliers.view')
        ->middlewareFor(['create', 'store'], 'permission:suppliers.create')
        ->middlewareFor(['edit', 'update'], 'permission:suppliers.edit')
        ->middlewareFor('destroy', 'permission:suppliers.delete');

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
        ->middlewareFor(['index', 'show'], 'permission:racks.view')
        ->middlewareFor(['create', 'store'], 'permission:racks.create')
        ->middlewareFor(['edit', 'update'], 'permission:racks.edit')
        ->middlewareFor('destroy', 'permission:racks.delete');

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
        ->middlewareFor(['index', 'show'], 'permission:locations.view')
        ->middlewareFor(['create', 'store'], 'permission:locations.create')
        ->middlewareFor(['edit', 'update'], 'permission:locations.edit')
        ->middlewareFor('destroy', 'permission:locations.delete');

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
        Route::get('/preview-lot-code', [PurchaseController::class, 'previewLotCode'])
            ->middleware('permission:purchases.create')->name('preview-lot-code');
        Route::patch('/{purchase}/post', [PurchaseController::class, 'post'])
            ->whereNumber('purchase')->middleware('permission:purchases.post')->name('post');
        Route::patch('/{purchase}/cancel', [PurchaseController::class, 'cancel'])
            ->whereNumber('purchase')->middleware('permission:purchases.edit')->name('cancel');
        Route::get('/{purchase}/print-labels', [PurchaseController::class, 'printLabels'])
            ->whereNumber('purchase')->middleware('permission:purchases.view')->name('print-labels');
        Route::get('/{purchase}/invoice', [PurchaseController::class, 'invoice'])
            ->whereNumber('purchase')->middleware('permission:purchases.view')->name('invoice');
        Route::get('/{purchase}/invoice/pdf', [PurchaseController::class, 'invoicePdf'])
            ->whereNumber('purchase')->middleware('permission:purchases.view')->name('invoice.pdf');

        // ── Payments ──
        Route::post('/{purchase}/payments', [PurchaseController::class, 'addPayment'])
            ->whereNumber('purchase')->middleware('permission:purchases.edit')->name('payments.store');
        Route::delete('/{purchase}/payments/{payment}', [PurchaseController::class, 'removePayment'])
            ->whereNumber('purchase')->whereNumber('payment')
            ->middleware('permission:purchases.edit')->name('payments.destroy');
    });
    Route::resource('purchases', PurchaseController::class)->whereNumber('purchase')
        ->middlewareFor(['index', 'show'], 'permission:purchases.view')
        ->middlewareFor(['create', 'store'], 'permission:purchases.create')
        ->middlewareFor(['edit', 'update'], 'permission:purchases.edit')
        ->middlewareFor('destroy', 'permission:purchases.delete');

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
        ->middlewareFor(['index', 'show'], 'permission:customers.view')
        ->middlewareFor(['create', 'store'], 'permission:customers.create')
        ->middlewareFor(['edit', 'update'], 'permission:customers.edit')
        ->middlewareFor('destroy', 'permission:customers.delete');

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
        Route::post('/{sale}/shipping-status/{status}', [SaleController::class, 'updateShippingStatus'])
            ->whereNumber('sale')->whereIn('status', Sale::SHIPPING_STATUSES)
            ->middleware('permission:sales.edit')->name('shipping-status');
        Route::post('/{sale}/shipping-details', [SaleController::class, 'updateShippingDetails'])
            ->whereNumber('sale')->middleware('permission:sales.edit')->name('shipping-details');

        // ── Payments ──
        Route::post('/{sale}/payments', [SaleController::class, 'addPayment'])
            ->whereNumber('sale')->middleware('permission:sales.edit')->name('payments.store');
        Route::delete('/{sale}/payments/{payment}', [SaleController::class, 'removePayment'])
            ->whereNumber('sale')->whereNumber('payment')
            ->middleware('permission:sales.edit')->name('payments.destroy');
    });

    Route::resource('sales', SaleController::class)->whereNumber('sale')
        ->middlewareFor(['index', 'show'], 'permission:sales.view')
        ->middlewareFor(['create', 'store'], 'permission:sales.create')
        ->middlewareFor(['edit', 'update'], 'permission:sales.edit')
        ->middlewareFor('destroy', 'permission:sales.delete');

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
        Route::get('/category-data', [StockController::class, 'categoryData'])
            ->middleware('permission:stock.view')->name('category-data');
        Route::get('/by-stone-data', [StockController::class, 'byStoneData'])
            ->middleware('permission:stock.view')->name('by-stone-data');
        Route::get('/movements-data', [StockController::class, 'movementsData'])
            ->middleware('permission:stock.view')->name('movements-data');
        Route::get('/search-products', [StockController::class, 'searchProducts'])
            ->middleware('permission:stock.view')->name('search-products');
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
        Route::get('/search-pieces', [StockTransferController::class, 'searchPieces'])
            ->middleware('permission:stock-transfers.create')->name('search-pieces');
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
        ->middlewareFor(['index', 'show'], 'permission:stock-transfers.view')
        ->middlewareFor(['create', 'store'], 'permission:stock-transfers.create')
        ->middlewareFor(['edit', 'update'], 'permission:stock-transfers.edit')
        ->middlewareFor('destroy', 'permission:stock-transfers.delete');

    /*
    |--------------------------------------------------------------------------
    | Stock Audits (physical stock-take)
    |--------------------------------------------------------------------------
    */
    Route::prefix('stock-audits')->name('stock-audits.')->group(function () {
        Route::get('/data', [StockAuditController::class, 'data'])
            ->middleware('permission:stock-audits.view')->name('data');
        Route::get('/{stockAudit}/scan', [StockAuditController::class, 'scanScreen'])
            ->whereNumber('stockAudit')->middleware('permission:stock-audits.scan')->name('scan');
        Route::post('/{stockAudit}/scan', [StockAuditController::class, 'scan'])
            ->whereNumber('stockAudit')->middleware('permission:stock-audits.scan')->name('scan.store');
        Route::post('/{stockAudit}/undo-scan', [StockAuditController::class, 'undoScan'])
            ->whereNumber('stockAudit')->middleware('permission:stock-audits.scan')->name('undo-scan');
        Route::post('/{stockAudit}/complete', [StockAuditController::class, 'complete'])
            ->whereNumber('stockAudit')->middleware('permission:stock-audits.complete')->name('complete');
        Route::post('/{stockAudit}/cancel', [StockAuditController::class, 'cancel'])
            ->whereNumber('stockAudit')->middleware('permission:stock-audits.complete')->name('cancel');
        Route::post('/{stockAudit}/write-off-missing', [StockAuditController::class, 'writeOffMissing'])
            ->whereNumber('stockAudit')->middleware('permission:stock-audits.write-off')->name('write-off-missing');
        Route::get('/{stockAudit}/missing-data', [StockAuditController::class, 'missingData'])
            ->whereNumber('stockAudit')->middleware('permission:stock-audits.view')->name('missing-data');
        Route::get('/{stockAudit}/export/pdf', [StockAuditController::class, 'exportPdf'])
            ->whereNumber('stockAudit')->middleware('permission:stock-audits.view')->name('export.pdf');
        Route::get('/{stockAudit}/export/excel', [StockAuditController::class, 'exportExcel'])
            ->whereNumber('stockAudit')->middleware('permission:stock-audits.view')->name('export.excel');
    });
    Route::resource('stock-audits', StockAuditController::class)
        ->only(['index', 'create', 'store', 'show'])
        ->whereNumber('stockAudit')
        ->parameters(['stock-audits' => 'stockAudit'])
        ->middlewareFor(['index', 'show'], 'permission:stock-audits.view')
        ->middlewareFor(['create', 'store'], 'permission:stock-audits.create');

    /*
    |--------------------------------------------------------------------------
    | Barcode History / Product Lookup
    |--------------------------------------------------------------------------
    */
    Route::prefix('barcode-history')->name('barcode-history.')->middleware('permission:stock.view')->group(function () {
        Route::get('/',        [BarcodeHistoryController::class, 'index'])->name('index');
        Route::get('/lookup',  [BarcodeHistoryController::class, 'lookup'])->name('lookup');
    });

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
        ->middlewareFor(['index', 'show'], 'permission:banners.view')
        ->middlewareFor(['create', 'store'], 'permission:banners.create')
        ->middlewareFor(['edit', 'update'], 'permission:banners.edit')
        ->middlewareFor('destroy', 'permission:banners.delete');

    /*
    |--------------------------------------------------------------------------
    | Blog
    |--------------------------------------------------------------------------
    */
    Route::prefix('blogs')->name('blogs.')->group(function () {
        Route::get('/data', [BlogController::class, 'data'])
            ->middleware('permission:blogs.view')->name('data');
        Route::patch('/{blog}/toggle-status', [BlogController::class, 'toggleStatus'])
            ->whereNumber('blog')->middleware('permission:blogs.edit')->name('toggle-status');
    });
    Route::resource('blogs', BlogController::class)->whereNumber('blog')
        ->middlewareFor(['index', 'show'], 'permission:blogs.view')
        ->middlewareFor(['create', 'store'], 'permission:blogs.create')
        ->middlewareFor(['edit', 'update'], 'permission:blogs.edit')
        ->middlewareFor('destroy', 'permission:blogs.delete');

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
        ->middlewareFor(['index', 'show'], 'permission:users.view')
        ->middlewareFor(['create', 'store'], 'permission:users.create')
        ->middlewareFor(['edit', 'update'], 'permission:users.edit')
        ->middlewareFor('destroy', 'permission:users.delete');

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
        ->middlewareFor(['index', 'show'], 'permission:roles.view')
        ->middlewareFor(['create', 'store'], 'permission:roles.create')
        ->middlewareFor(['edit', 'update'], 'permission:roles.edit')
        ->middlewareFor('destroy', 'permission:roles.delete');

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

});
