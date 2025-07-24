<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::prefix('iso-api')->group(function () {
    // Orders routes
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{id}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    
    Route::get('/forms/create', [OrderController::class, 'create'])->name('forms.create');
    // Products routes
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('index');
        Route::get('/search', [ProductController::class, 'search'])->name('search');
        Route::get('/create', [ProductController::class, 'create'])->name('create');
        Route::post('/store', [ProductController::class, 'store'])->name('store');
        Route::get('/scheme', [ProductController::class, 'scheme'])->name('scheme');

        // CSV Import routes
        Route::get('/import', [ProductController::class, 'showImport'])->name('import.show');
        Route::post('/import', [ProductController::class, 'import'])->name('import');
        Route::get('/import/template', [ProductController::class, 'downloadTemplate'])->name('import.template');
        Route::get('/import/validate', [ProductController::class, 'validateCsv'])->name('import.validate');
    });
});

// Default welcome route
Route::get('/', function () {
    return 'Laravel is working!';
});