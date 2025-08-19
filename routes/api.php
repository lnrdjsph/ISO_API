<?php

use App\Http\Controllers\TestController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\Icard\UserPointsController;
use App\Http\Controllers\Icard\TransactionHistoryController;
use App\Http\Controllers\RMSCommerceSynchronizationController;
use App\Http\Controllers\MRCTenderController;
use App\Http\Controllers\ECRController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('iso-api')->group(function () {
    Route::get('/test', [TestController::class, 'testConnection']);
    Route::post('/get-user-data', [UserController::class, 'getUserData']);
    Route::post('/otp-send', [OtpController::class, 'sendOtp']);
    Route::post('/otp-verify', [OtpController::class, 'verifyOtp']);
    Route::post('/loyalty-points', [UserPointsController::class, 'getLoyaltyPoints']);
    Route::post('/transactions', [TransactionHistoryController::class, 'getTransactions']);

   
});

Route::post('/payment-data', [ECRController::class, 'getPaymentData']);

use App\Http\Controllers\OracleRmsController;

Route::post('/oracle-rms/item', [OracleRmsController::class, 'fetchItemData']);


Route::prefix('v1')->group(function () {
    Route::prefix('rms-sync')->group(function () {
        // Start synchronization
        Route::post('/synchronize', [RMSCommerceSynchronizationController::class, 'synchronize']);
        
        // Get synchronization status
        Route::get('/status', [RMSCommerceSynchronizationController::class, 'status']);
        
        // Get logs (optional)
        Route::get('/logs', [RMSCommerceSynchronizationController::class, 'getLogs']);
    });
});

Route::post('/mrc/tender', [MRCTenderController::class, 'process']);

use App\Helpers\ISO8583Client;

Route::get('/test-iso', function () {
    $iso = new ISO8583Client();

    $iso->setMTI('0200');                                 // Financial transaction request
    $iso->setField(3, '590000');                          // Processing Code (e.g. redeem)
    $iso->setField(4, '000000010000');                    // Amount = PHP 100.00 in cents (12 digits)
    $iso->setField(11, '123456');                         // STAN (System Trace Audit Number)
    $iso->setField(22, '012');                            // POS Entry Mode (manual key entry)
    $iso->setField(24, '177');                            // NII (Network International Identifier)
    $iso->setField(25, '00');                             // POS Condition Code (Normal)
    $iso->setField(35, '8888721709043567=20121010000059600');  // Track 2 data (no LL prefix)
    $iso->setField(41, '99999024');                       // Terminal ID (8 chars, will be padded)
    $iso->setField(42, '000017770000606');                // Merchant ID (15 chars, will be padded)

    try {
        $response = $iso->send();

        return response()->json([
            'field_39' => $response->getField(39), // Response Code
            'field_11' => $response->getField(11), // STAN
            'field_37' => $response->getField(37), // RRN
            'field_4'  => $response->getField(4),  // Authorized Amount
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
});
