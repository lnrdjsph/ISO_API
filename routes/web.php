<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;

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

Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
Route::get('/orders/{id}', [OrderController::class, 'show'])->name('orders.show');
Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
Route::get('/orders/create', [OrderController::class, 'create'])->name('orders.create');


Route::get('/', function () {
    return view('welcome');
});


// routes/web.php

use App\Http\Controllers\ProductController;
Route::get('/', function () {
    return 'Laravel is working!';
});
Route::prefix('products')->name('products.')->group(function () {
    Route::get('/', [ProductController::class, 'index'])->name('index');
    Route::get('/search', [ProductController::class, 'search'])->name('search');
    Route::get('/create', [ProductController::class, 'create'])->name('create');
    Route::post('/store', [ProductController::class, 'store'])->name('store');
    
    // CSV Import routes
    Route::get('/import', [ProductController::class, 'showImport'])->name('import.show');
    Route::post('/import', [ProductController::class, 'import'])->name('import');
    Route::get('/import/template', [ProductController::class, 'downloadTemplate'])->name('import.template');
    Route::post('/import/validate', [ProductController::class, 'validateCsv'])->name('import.validate');
});
