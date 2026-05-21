<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — NexusBank
|--------------------------------------------------------------------------
| RESTful API v1 for mobile app integration.
| Authentication via Laravel Sanctum (token-based).
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // ── Public Auth ─────────────────────────────────────────
    Route::post('/auth/register', [\App\Http\Controllers\Api\V1\AuthApiController::class, 'register']);
    Route::post('/auth/login', [\App\Http\Controllers\Api\V1\AuthApiController::class, 'login'])->middleware('throttle:login');
    Route::post('/auth/verify-otp', [\App\Http\Controllers\Api\V1\AuthApiController::class, 'verifyOtp'])->middleware('throttle:otp');

    // ── Authenticated ───────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::post('/auth/logout', [\App\Http\Controllers\Api\V1\AuthApiController::class, 'logout']);
        Route::get('/auth/user', [\App\Http\Controllers\Api\V1\AuthApiController::class, 'user']);

        // Dashboard
        Route::get('/dashboard/summary', [\App\Http\Controllers\Api\V1\DashboardApiController::class, 'summary']);

        // Accounts
        Route::get('/accounts', [\App\Http\Controllers\Api\V1\AccountApiController::class, 'index']);
        Route::get('/accounts/{account}', [\App\Http\Controllers\Api\V1\AccountApiController::class, 'show']);
        Route::post('/accounts', [\App\Http\Controllers\Api\V1\AccountApiController::class, 'store']);

        // Transactions
        Route::get('/transactions', [\App\Http\Controllers\Api\V1\TransactionApiController::class, 'index']);
        Route::get('/transactions/{transaction}', [\App\Http\Controllers\Api\V1\TransactionApiController::class, 'show']);
        Route::post('/transactions/transfer', [\App\Http\Controllers\Api\V1\TransactionApiController::class, 'transfer'])->middleware('throttle:api_transfer');

        // Beneficiaries
        Route::apiResource('/beneficiaries', \App\Http\Controllers\Api\V1\BeneficiaryApiController::class)->names('api.beneficiaries');

        // Cards
        Route::get('/cards', [\App\Http\Controllers\Api\V1\CardApiController::class, 'index']);
        Route::post('/cards/{card}/freeze', [\App\Http\Controllers\Api\V1\CardApiController::class, 'freeze']);
        Route::post('/cards/{card}/unfreeze', [\App\Http\Controllers\Api\V1\CardApiController::class, 'unfreeze']);

        // Notifications
        Route::get('/notifications', [\App\Http\Controllers\Api\V1\NotificationApiController::class, 'index']);
        Route::post('/notifications/{notification}/read', [\App\Http\Controllers\Api\V1\NotificationApiController::class, 'markAsRead']);

        // Profile
        Route::get('/profile', [\App\Http\Controllers\Api\V1\ProfileApiController::class, 'show']);
        Route::put('/profile', [\App\Http\Controllers\Api\V1\ProfileApiController::class, 'update']);
    });
});
