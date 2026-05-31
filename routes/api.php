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
        Route::post('/cards', [\App\Http\Controllers\Api\V1\CardApiController::class, 'store']);
        Route::get('/cards/{card}', [\App\Http\Controllers\Api\V1\CardApiController::class, 'show']);
        Route::post('/cards/{card}/freeze', [\App\Http\Controllers\Api\V1\CardApiController::class, 'freeze']);
        Route::post('/cards/{card}/unfreeze', [\App\Http\Controllers\Api\V1\CardApiController::class, 'unfreeze']);
        Route::post('/cards/{card}/reveal', [\App\Http\Controllers\Api\V1\CardApiController::class, 'reveal']);
        Route::put('/cards/{card}/limits', [\App\Http\Controllers\Api\V1\CardApiController::class, 'updateLimits']);
        Route::post('/cards/{card}/toggle-contactless', [\App\Http\Controllers\Api\V1\CardApiController::class, 'toggleContactless']);
        Route::post('/cards/{card}/toggle-online', [\App\Http\Controllers\Api\V1\CardApiController::class, 'toggleOnline']);
        Route::post('/cards/{card}/toggle-international', [\App\Http\Controllers\Api\V1\CardApiController::class, 'toggleInternational']);
        Route::post('/cards/{card}/change-pin', [\App\Http\Controllers\Api\V1\CardApiController::class, 'requestPinChange']);
        Route::post('/cards/{card}/approve', [\App\Http\Controllers\Api\V1\CardApiController::class, 'approve']);
        Route::post('/cards/{card}/reject', [\App\Http\Controllers\Api\V1\CardApiController::class, 'reject']);

        // Transfers
        Route::get('/transfers/pending', [\App\Http\Controllers\Api\V1\TransferApiController::class, 'pending']);
        Route::post('/transfers', [\App\Http\Controllers\Api\V1\TransferApiController::class, 'store']);
        Route::post('/transfers/{id}/accept', [\App\Http\Controllers\Api\V1\TransferApiController::class, 'accept']);
        Route::post('/transfers/{id}/decline', [\App\Http\Controllers\Api\V1\TransferApiController::class, 'decline']);
        Route::post('/transfers/{id}/cancel', [\App\Http\Controllers\Api\V1\TransferApiController::class, 'cancel']);
        Route::match(['GET', 'POST'], '/transfers/convert', [\App\Http\Controllers\Api\V1\TransferApiController::class, 'convert']);
        Route::get('/transfers/exchange-rate', [\App\Http\Controllers\Api\V1\TransferApiController::class, 'exchangeRate']);

        // Loans
        Route::get('/loans', [\App\Http\Controllers\Api\V1\LoanApiController::class, 'index']);
        Route::post('/loans', [\App\Http\Controllers\Api\V1\LoanApiController::class, 'store']);
        Route::get('/loans/{id}', [\App\Http\Controllers\Api\V1\LoanApiController::class, 'show']);
        Route::post('/loans/{id}/pay', [\App\Http\Controllers\Api\V1\LoanApiController::class, 'pay']);

        // Notifications
        Route::get('/notifications', [\App\Http\Controllers\Api\V1\NotificationApiController::class, 'index']);
        Route::get('/notifications/unread-count', [\App\Http\Controllers\Api\V1\NotificationApiController::class, 'unreadCount']);
        Route::get('/notifications/latest', [\App\Http\Controllers\Api\V1\NotificationApiController::class, 'latest']);
        Route::post('/notifications/read-all', [\App\Http\Controllers\Api\V1\NotificationApiController::class, 'markAllAsRead']);
        Route::post('/notifications/{notification}/read', [\App\Http\Controllers\Api\V1\NotificationApiController::class, 'markAsRead']);

        // Profile
        Route::get('/profile', [\App\Http\Controllers\Api\V1\ProfileApiController::class, 'show']);
        Route::put('/profile', [\App\Http\Controllers\Api\V1\ProfileApiController::class, 'update']);
        Route::put('/profile/password', [\App\Http\Controllers\Api\V1\ProfileApiController::class, 'changePassword']);
        Route::get('/profile/security', [\App\Http\Controllers\Api\V1\ProfileApiController::class, 'security']);

        // KYC
        Route::get('/profile/kyc/status', [\App\Http\Controllers\Api\V1\ProfileApiController::class, 'kycStatus']);
        Route::post('/profile/kyc', [\App\Http\Controllers\Api\V1\ProfileApiController::class, 'uploadKyc']);

        // Two-Factor Authentication (2FA)
        Route::get('/profile/2fa', [\App\Http\Controllers\Api\V1\ProfileApiController::class, 'showTwoFactor']);
        Route::post('/profile/2fa/enable', [\App\Http\Controllers\Api\V1\ProfileApiController::class, 'enableTwoFactor']);
        Route::post('/profile/2fa/disable', [\App\Http\Controllers\Api\V1\ProfileApiController::class, 'disableTwoFactor']);
    });
});
