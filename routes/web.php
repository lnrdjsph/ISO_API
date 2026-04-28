<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\FormsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportsController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\OracleTransferController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\InventoryExportController;
use App\Http\Controllers\FileManagerController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\WmsLogController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Auth\AccountController;

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

Route::middleware(['auth', 'session.expired'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard'); // protected home

    Route::get('/dashboard', fn() => view('dashboard'))->name('dashboard.view');

    Route::post('/logout', function () {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect(env('FORTIFY_LOGOUT_REDIRECT', '/login'));
    })->name('logout');

    // Handle GET /logout gracefully (ZAP, browser back button, direct URL access)
    Route::get('/logout', function () {
        return redirect()->route('login');
    })->name('logout.get');
});



Route::prefix('b2b2c')->middleware(['auth', 'session.expired'])->group(function () {

    // Orders Routes
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('/{id}', [OrderController::class, 'show'])->name('show');
        Route::put('/{order}', [OrderController::class, 'update'])->name('update');

        Route::post('/cancel-items', [OrderController::class, 'cancelItems'])->name('cancel-items');

        Route::post('/archive', [OrderController::class, 'archive'])->name('archive');
        Route::post('/cancel', [OrderController::class, 'cancel'])->name('cancel');
        Route::post('/restore', [OrderController::class, 'restore'])->name('restore');
        Route::post('/complete', [OrderController::class, 'complete'])->name('complete');
        Route::post('/for_approval', [OrderController::class, 'forApproval'])->name('for_approval');
        Route::post('/approve', [OrderController::class, 'approveOrder'])->name('approve');
        //     ->name('approve')
        //     ->middleware(['csp.eval']);
        Route::post('/reject', [OrderController::class, 'rejectOrder'])->name('reject');

        Route::get('/{id}/print-sof', [OrderController::class, 'printSOF'])->name('print.sof');
        Route::get('/{id}/print-sof-invoice', [OrderController::class, 'printSOFInvoice'])->name('print.sof_invoice');
        Route::get('/{id}/freebies-pdf', [OrderController::class, 'generateFreebiesForm'])->name('print.freebies');
        Route::get('/{id}/print-order-slip', [OrderController::class, 'generateOrderSlip'])->name('print.order_slip');
    });

    // Forms Routes
    Route::prefix('forms')->name('forms.')->group(function () {
        Route::get('/sof', [FormsController::class, 'sof'])->name('sof');
        Route::get('/sof_search', [FormsController::class, 'search'])->name('sof_search');
        Route::post('/sof', [FormsController::class, 'sof_submit'])->name('sof_submit');
        Route::get('/rof', [FormsController::class, 'rof'])->name('rof');
        Route::post('/rof', [FormsController::class, 'rof_submit'])->name('rof_submit');
        Route::post('/card-info', [FormsController::class, 'getCardInfo'])->name('get_card_info');
    });

    // Products Routes
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('index');
        Route::get('/search', [ProductController::class, 'search'])->name('search');
        Route::get('/create', [ProductController::class, 'create'])->name('create');
        Route::post('/store', [ProductController::class, 'store'])->name('store');
        Route::get('/scheme', [ProductController::class, 'scheme'])->name('scheme');
        Route::get('/export', [ProductController::class, 'export'])->name('export');
        Route::get('/allocation', [ProductController::class, 'getAllocation'])->name('allocation');

        Route::post('/bulk-update', [ProductController::class, 'bulkUpdate'])->name('bulk-update');
        Route::post('/bulk-archive', [ProductController::class, 'bulkArchive'])->name('bulk-archive');
        Route::post('/bulk-restore', [ProductController::class, 'bulkRestore'])->name('bulk-restore');

        Route::get('/skus', [ProductController::class, 'getSkus'])->name('get-skus');

        Route::prefix('import')->name('import.')->group(function () {
            Route::get('/', [ProductController::class, 'showImport'])->name('show');
            Route::post('/', [ProductController::class, 'import'])->name('upload');
            Route::get('/template', [ProductController::class, 'downloadTemplate'])->name('template');
            Route::get('/validate', [ProductController::class, 'validateCsv'])->name('validate');
        });
    });
});


// ✅ FIXED: Added auth + session.expired middleware (was completely unprotected)
Route::prefix('/users')->name('users.')->middleware(['auth', 'session.expired'])->group(function () {
    Route::get('/', [UserController::class, 'index'])->name('index');
    Route::get('/create', [UserController::class, 'create'])->name('create');
    Route::post('/', [UserController::class, 'store'])->name('store');
    Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
    Route::put('/{user}', [UserController::class, 'update'])->name('update');
    Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
});





// ✅ FIXED: Added auth + session.expired middleware (was completely unprotected)
Route::prefix('update-allocations')->middleware(['auth', 'session.expired'])->group(function () {
    Route::post('/', [ProductController::class, 'wmsUpdate'])
        ->name('update.allocations');

    Route::get('/status', [ProductController::class, 'wmsStatus'])
        ->name('update.allocations.status');
});


// ✅ FIXED: Added auth + session.expired middleware (was completely unprotected)
Route::prefix('reports')->middleware(['auth', 'session.expired', 'csp.eval'])->group(function () {
    Route::get('/sales', [ReportsController::class, 'salesReport'])
        ->name('reports.sales');

    Route::get('/payments', [ReportsController::class, 'paymentReport'])
        ->name('reports.payments');

    Route::get('/orders', [ReportsController::class, 'ordersReport'])
        ->name('reports.orders');

    Route::get('/orders/export', [ReportsController::class, 'exportOrdersReport'])
        ->name('reports.orders.export');

    Route::get('/freebies', [ReportsController::class, 'freebiesReport'])
        ->name('reports.freebies');

    Route::get('/sales/export', [ReportsController::class, 'exportCsv'])
        ->name('reports.sales.export');
});


// ✅ FIXED: Added auth + session.expired middleware (was completely unprotected)
Route::post('/oracle/transfer', [OracleTransferController::class, 'send'])
    ->name('oracle.transfer')
    ->middleware(['auth', 'session.expired']);


// Unauthorized route (intentionally public)
Route::view('/403', 'errors.403');


// Session check (intentionally public — used by JS polling)
Route::get('/check-session', function () {
    return response()->json(['authenticated' => Auth::check()]);
})->name('check.session');


Route::prefix('user-guide')->name('user-guide.')->middleware(['auth', 'session.expired'])->group(function () {
    Route::get('/document', fn() => view('user_guide.document'))->name('document');
});

// ✅ FIXED: Added auth + session.expired middleware (was completely unprotected)
Route::prefix('others')->name('others.')->middleware(['auth', 'session.expired'])->group(function () {
    Route::get('/inventory-upload', [InventoryExportController::class, 'showForm'])
        ->name('inventory.form');

    Route::post('/inventory-export', [InventoryExportController::class, 'export'])
        ->name('inventory.export');

    // others xlsx editor from views/documents/xlsx-editor.blade.php
    Route::get('/xlsx-editor', fn() => view('documents.xlsx-editor'))->name('xlsx-editor');

    // File Manager under others
    Route::prefix('filemanager')->name('filemanager.')->group(function () {
        Route::get('/',           [FileManagerController::class, 'index'])->name('index');
        Route::post('/upload',    [FileManagerController::class, 'upload'])->name('upload');
        Route::get('/download',   [FileManagerController::class, 'download'])->name('download');
        Route::post('/rename',    [FileManagerController::class, 'rename'])->name('rename');
        Route::post('/delete',    [FileManagerController::class, 'delete'])->name('delete');
        Route::post('/mkdir',     [FileManagerController::class, 'mkdir'])->name('mkdir');
    });
});

Route::middleware(['auth'])->prefix('settings')->name('settings.')->group(function () {

    Route::get('/',                        [SettingsController::class, 'index'])->name('index');

    // Stores
    Route::post('stores/enroll',           [SettingsController::class, 'enrollStore'])->name('stores.enroll');
    Route::put('stores/{code}',            [SettingsController::class, 'updateStore'])->name('stores.update');
    Route::delete('stores/{code}',         [SettingsController::class, 'deactivateStore'])->name('stores.deactivate');

    // Warehouses
    Route::post('warehouses',              [SettingsController::class, 'storeWarehouse'])->name('warehouses.store');
    Route::put('warehouses/{code}',        [SettingsController::class, 'updateWarehouse'])->name('warehouses.update');

    // Regions
    Route::post('regions',                 [SettingsController::class, 'storeRegion'])->name('regions.store');
    Route::put('regions/{key}',            [SettingsController::class, 'updateRegion'])->name('regions.update');

    // Region Emails
    Route::post('regions/{key}/emails', [SettingsController::class, 'storeRegionEmail'])->name('settings.regions.emails.store');
    Route::delete('region-emails/{id}', [SettingsController::class, 'destroyRegionEmail'])->name('settings.regions.emails.destroy');
});




Route::middleware(['auth'])->group(function () {
    Route::get('/logs',       [WmsLogController::class, 'index'])->name('logs');
    Route::get('/logs/fetch', [WmsLogController::class, 'fetch'])->name('logs.fetch');
});


// Forgot password
Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])
    ->name('password.request');
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])
    ->name('password.email');

// Reset password
Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])
    ->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])
    ->name('password.update');



Route::get('/account', [AccountController::class, 'show'])->name('account.show');
Route::put('/account', [AccountController::class, 'update'])->name('account.update');
Route::put('/account/password', [AccountController::class, 'updatePassword'])->name('account.password');
