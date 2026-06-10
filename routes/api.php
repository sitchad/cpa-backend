<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OfferController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\WithdrawController;
use App\Http\Controllers\Api\PostbackController;
use App\Http\Controllers\Api\AdminController;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);
});

Route::post('/postback', [PostbackController::class, 'handle']);
Route::get('/postback',  [PostbackController::class, 'handle']);

Route::middleware(['auth:sanctum', 'anti.fraud'])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me',      [AuthController::class, 'me']);

    Route::get('/offers',              [OfferController::class, 'index']);
    Route::get('/offers/{id}',         [OfferController::class, 'show']);
    Route::post('/offers/{id}/click',  [OfferController::class, 'click']);

    Route::get('/wallet',          [WalletController::class, 'index']);
    Route::get('/wallet/history',  [WalletController::class, 'history']);

    Route::get('/withdraw',    [WithdrawController::class, 'index']);
    Route::post('/withdraw',   [WithdrawController::class, 'store']);
    Route::get('/withdraw/{id}', [WithdrawController::class, 'show']);
});

Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/stats',                          [AdminController::class, 'stats']);
    Route::get('/users',                          [AdminController::class, 'users']);
    Route::get('/users/{id}',                     [AdminController::class, 'userDetail']);
    Route::put('/users/{id}/ban',                 [AdminController::class, 'banUser']);
    Route::get('/withdrawals',                    [AdminController::class, 'withdrawals']);
    Route::put('/withdrawals/{id}/approve',       [AdminController::class, 'approveWithdrawal']);
    Route::put('/withdrawals/{id}/reject',        [AdminController::class, 'rejectWithdrawal']);
    Route::get('/postbacks',                      [AdminController::class, 'postbacks']);
    Route::get('/fraud-logs',                     [AdminController::class, 'fraudLogs']);
});
