<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Categories
|--------------------------------------------------------------------------
| `data` and `toggle-status` are registered BEFORE the resource so they win
| in route matching. The resource is constrained to numeric {category} as a
| belt-and-braces safeguard against future literal-path collisions.
*/
Route::prefix('categories')->name('categories.')->group(function () {
    Route::get('/data', [CategoryController::class, 'data'])->name('data');
    Route::patch('/{category}/toggle-status', [CategoryController::class, 'toggleStatus'])
        ->whereNumber('category')
        ->name('toggle-status');
});

Route::resource('categories', CategoryController::class)
    ->whereNumber('category');

/*
|--------------------------------------------------------------------------
| Products
|--------------------------------------------------------------------------
| Non-resourceful routes (data feed, ajax helpers, bulk + toggles) are
| registered BEFORE the resource so they win route matching. The resource
| binding is restricted to a numeric {product} segment so the literal
| sub-paths ("data", "barcodes/...", "subcategories/...", "bulk-website-toggle")
| can never be mistaken for an id.
*/
Route::prefix('products')->name('products.')->group(function () {
    // Yajra DataTables AJAX feed.
    Route::get('/data', [ProductController::class, 'data'])->name('data');

    // Cascading dropdown: returns active subcategories under a top-level category.
    Route::get('/subcategories/{category}', [ProductController::class, 'subcategoriesByParent'])
        ->whereNumber('category')
        ->name('subcategories');

    // Barcode helpers used by the multi-barcode panel.
    Route::post('/barcodes/generate', [ProductController::class, 'generateBarcode'])
        ->name('barcodes.generate');
    Route::post('/barcodes/validate', [ProductController::class, 'validateBarcode'])
        ->name('barcodes.validate');

    // Bulk operations — capped at 500 ids per request (spec §6.5).
    Route::post('/bulk-website-toggle', [ProductController::class, 'bulkWebsiteToggle'])
        ->name('bulk-website-toggle');

    // Single-row toggles (product status + website visibility).
    Route::patch('/{product}/toggle-status', [ProductController::class, 'toggleStatus'])
        ->whereNumber('product')
        ->name('toggle-status');
    Route::patch('/{product}/toggle-website', [ProductController::class, 'toggleWebsite'])
        ->whereNumber('product')
        ->name('toggle-website');
});

Route::resource('products', ProductController::class)
    ->whereNumber('product');

