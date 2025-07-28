<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\FormsController;
use App\Http\Controllers\DashboardController;

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

Route::prefix('b2b2c')->group(function () {

    // Orders Routes
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('/{id}', [OrderController::class, 'show'])->name('show');
    });

    // Forms Routes
    Route::prefix('forms')->name('forms.')->group(function () {
        Route::get('/sof', [FormsController::class, 'sof'])->name('sof');
        Route::post('/sof', [FormsController::class, 'sof_submit'])->name('sof_submit');
        Route::get('/rof', [FormsController::class, 'rof'])->name('rof');
        Route::post('/rof', [FormsController::class, 'rof_submit'])->name('rof_submit');
    });

    // Products Routes
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('index');
        Route::get('/search', [ProductController::class, 'search'])->name('search');
        Route::get('/create', [ProductController::class, 'create'])->name('create');
        Route::post('/store', [ProductController::class, 'store'])->name('store');
        Route::get('/scheme', [ProductController::class, 'scheme'])->name('scheme');

        // CSV Import Routes
        Route::prefix('import')->name('import.')->group(function () {
            Route::get('/', [ProductController::class, 'showImport'])->name('show');
            Route::post('/', [ProductController::class, 'import'])->name('upload');
            Route::get('/template', [ProductController::class, 'downloadTemplate'])->name('template');
            Route::get('/validate', [ProductController::class, 'validateCsv'])->name('validate');
        });
    });

});


// Default welcome route
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');