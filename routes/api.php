<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CallController;
use App\Http\Controllers\Api\ContactMatchController;
use App\Http\Controllers\Api\DeviceCommandController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\DeviceLocationController;
use App\Http\Controllers\Api\FcmTokenController;
use App\Http\Controllers\Api\LostModeController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\RecoveryController;
use App\Http\Controllers\Api\V1\ConnectionController;
use App\Http\Controllers\Api\V1\CustomRouteController;
use App\Http\Controllers\Api\V1\LocationRequestController;
use App\Http\Controllers\Api\V1\ProfileController;

Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | HEALTH
    |--------------------------------------------------------------------------
    */

    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'service' => 'webs-people-api',
            'version' => 'v1',
            'timestamp' => now()->toISOString(),
        ]);
    });

    /*
    |--------------------------------------------------------------------------
    | AUTH
    |--------------------------------------------------------------------------
    */

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
            '/set-password',
            [AuthController::class, 'setPassword']
        );

        Route::post(
            '/resend-code',
            [AuthController::class, 'resendVerificationCode']
        );

        Route::post(
            '/login',
            [AuthController::class, 'login']
        );

        Route::middleware('auth:sanctum')->group(function () {

            Route::get(
                '/me',
                [AuthController::class, 'me']
            );

            Route::post(
                '/logout',
                [AuthController::class, 'logout']
            );
        });
    });

    /*
    |--------------------------------------------------------------------------
    | PUSH NOTIFICATIONS (FCM)
    |--------------------------------------------------------------------------
    */

    Route::middleware('auth:sanctum')->group(function () {

        Route::post(
            '/fcm-token',
            [FcmTokenController::class, 'store']
        );

        Route::delete(
            '/fcm-token',
            [FcmTokenController::class, 'destroy']
        );
    });

    /*
    |--------------------------------------------------------------------------
    | DEVICES
    |--------------------------------------------------------------------------
    */

    Route::middleware('auth:sanctum')
        ->prefix('devices')
        ->group(function () {

            Route::get(
                '/',
                [DeviceController::class, 'index']
            );

            Route::post(
                '/',
                [DeviceController::class, 'store']
            );

            Route::get(
                '/recovery',
                [RecoveryController::class, 'index']
            );

            Route::post(
                '/{device}/heartbeat',
                [DeviceController::class, 'heartbeat']
            )->middleware('throttle:60,1');

            Route::post(
                '/{device}/location',
                [DeviceLocationController::class, 'store']
            )->middleware('throttle:30,1');

            Route::get(
                '/{device}/locations',
                [DeviceLocationController::class, 'index']
            );

            Route::post(
                '/{device}/lost-mode',
                [LostModeController::class, 'enable']
            );

            Route::delete(
                '/{device}/lost-mode',
                [LostModeController::class, 'disable']
            );

            Route::get(
                '/{device}',
                [DeviceController::class, 'show']
            );

            Route::post(
                '/{device}/commands',
                [DeviceCommandController::class, 'store']
            );

            Route::get(
                '/{device}/commands',
                [DeviceCommandController::class, 'index']
            );

            Route::post(
                '/{device}/commands/{command}/ack',
                [DeviceCommandController::class, 'acknowledge']
            );
        });

    /*
    |--------------------------------------------------------------------------
    | CONTACTS
    |--------------------------------------------------------------------------
    */

    Route::middleware('auth:sanctum')->group(function () {

        Route::post(
            '/contacts/match',
            [ContactMatchController::class, 'match']
        );
    });

    /*
    |--------------------------------------------------------------------------
    | MESSAGES
    |--------------------------------------------------------------------------
    */

    Route::middleware('auth:sanctum')->group(function () {

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
            '/messages/{messageId}/read',
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

    /*
    |--------------------------------------------------------------------------
    | CALLS
    |--------------------------------------------------------------------------
    */

    Route::middleware('auth:sanctum')->group(function () {

        Route::post(
            '/calls/start',
            [CallController::class, 'start']
        );

        Route::get(
            '/calls/pending',
            [CallController::class, 'pending']
        );

        Route::post(
            '/calls/{callId}/event',
            [CallController::class, 'event']
        );

        Route::get(
            '/calls/{callId}/events',
            [CallController::class, 'events']
        );

        Route::post(
            '/calls/events/{event}/ack',
            [CallController::class, 'acknowledge']
        );
    });

    /*
    |--------------------------------------------------------------------------
    | LOCATION REQUESTS
    |--------------------------------------------------------------------------
    */

    Route::middleware('auth:sanctum')->group(function () {

        Route::post(
            '/location/codes',
            [LocationRequestController::class, 'generateCode']
        );

        Route::post(
            '/location/requests',
            [LocationRequestController::class, 'requestLocation']
        );

        Route::get(
            '/location/requests/pending',
            [LocationRequestController::class, 'pendingRequests']
        );

        Route::get(
            '/location/requests/chat/{userId}',
            [LocationRequestController::class, 'conversationRequests']
        );

        Route::post(
            '/location/requests/{requestId}/accept',
            [LocationRequestController::class, 'accept']
        );

        Route::post(
            '/location/requests/{requestId}/deny',
            [LocationRequestController::class, 'deny']
        );

        Route::post(
            '/location/requests/{requestId}/location',
            [LocationRequestController::class, 'updateLocation']
        );

        Route::get(
            '/location/requests/{requestId}/location',
            [LocationRequestController::class, 'getLocation']
        );

        Route::post(
            '/location/share',
            [LocationRequestController::class, 'shareLocation']
        );

        Route::delete(
            '/location/share',
            [LocationRequestController::class, 'stopSharing']
        );

        Route::get(
            '/location/people',
            [LocationRequestController::class, 'peopleLocations']
        );

        Route::post(
            '/location/custom-routes',
            [CustomRouteController::class, 'store']
        );

        Route::get(
            '/location/custom-routes/{locationRequestId}',
            [CustomRouteController::class, 'show']
        );

        Route::delete(
            '/location/custom-routes/{locationRequestId}',
            [CustomRouteController::class, 'destroy']
        );
    });

    /*
    |--------------------------------------------------------------------------
    | CONNECTIONS
    |--------------------------------------------------------------------------
    */

    Route::middleware('auth:sanctum')->group(function () {

        Route::post(
            '/connections/requests',
            [ConnectionController::class, 'sendRequest']
        );

        Route::get(
            '/connections/requests/pending',
            [ConnectionController::class, 'pendingRequests']
        );

        Route::post(
            '/connections/requests/{requestId}/accept',
            [ConnectionController::class, 'acceptRequest']
        );

        Route::post(
            '/connections/requests/{requestId}/reject',
            [ConnectionController::class, 'rejectRequest']
        );

        Route::get(
            '/connections',
            [ConnectionController::class, 'connections']
        );
    });

    /*
    |--------------------------------------------------------------------------
    | PROFILE
    |--------------------------------------------------------------------------
    */

    Route::middleware('auth:sanctum')->group(function () {

        Route::get(
            '/profile',
            [ProfileController::class, 'show']
        );

        Route::put(
            '/profile',
            [ProfileController::class, 'update']
        );

        Route::post(
            '/profile/photo',
            [ProfileController::class, 'uploadPhoto']
        );

        Route::delete(
            '/profile/photo',
            [ProfileController::class, 'removePhoto']
        );
    });
});