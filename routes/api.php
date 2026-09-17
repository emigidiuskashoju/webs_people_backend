<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContactMatchController;
use App\Http\Controllers\Api\MessageController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'service' => 'webs-people-api',
            'version' => 'v1',
            'timestamp' => now()->toISOString(),
        ]);
    });

    Route::prefix('auth')->group(function () {

        Route::post(
            '/register',
            [AuthController::class, 'register']
        );

        Route::post(
            '/verify-email',
            [AuthController::class, 'verifyEmail']
        );

        Route::post(
            '/resend-code',
            [AuthController::class, 'resendVerificationCode']
        );

        Route::middleware('auth:sanctum')
            ->group(function () {

                Route::get(
                    '/me',
                    [AuthController::class, 'me']
                );
            });
    });

    Route::middleware('auth:sanctum')
        ->post(
            '/contacts/match',
            [ContactMatchController::class, 'match']
        );

      Route::middleware('auth:sanctum')
    ->group(function () {

    Route::post(
        '/messages/send',
        [MessageController::class, 'send']
    );

    Route::get(
        '/messages/pending',
        [MessageController::class, 'pending']
    );

    Route::post(
        '/messages/{message}/ack',
        [MessageController::class, 'acknowledge']
    );

    Route::post(
        '/messages/{message}/read',
        [MessageController::class, 'markRead']
    );

    Route::get(
        '/messages/read-receipts',
        [MessageController::class, 'pendingReadReceipts']
    );

    Route::post(
        '/messages/read-receipts/{receipt}/ack',
        [MessageController::class, 'acknowledgeReadReceipt']
    );
});
});