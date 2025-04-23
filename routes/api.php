<?php

use App\Http\Controllers\TestController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\Icard\UserPointsController;

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
   
});
