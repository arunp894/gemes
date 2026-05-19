<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;

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
