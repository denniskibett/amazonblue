<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// API for other systems (owner, 3 Nov 2026): keys are made on /staff/api-keys and sent as "Authorization: Bearer <key>".
Route::prefix('v1')->middleware(['api.key', 'throttle:60,1'])->group(function () {
    // checks a key works
    Route::get('ping', fn (Request $request) => response()->json(['ok' => true, 'key' => $request->attributes->get('api_key')->name, 'time' => now()->toIso8601String()]));
});

// Mobile test app (owner, 11 Nov 2026): APK only for now. Every call carries an X-Api-Key made on /staff/api-keys
// with "Used by: Mobile app"; signed-in calls also carry the phone's Sanctum token. Off unless MOBILE_API_ENABLED.
Route::prefix('mobile/v1')->middleware(['api.key:mobile', 'throttle:120,1'])->group(function () {
    Route::get('ping', fn () => response()->json(['ok' => true, 'enabled' => (bool) config('mobile.enabled'), 'time' => now()->toIso8601String()]));
    Route::get('app', fn () => response()->json(['version' => config('mobile.apk.version'), 'notes' => config('mobile.apk.notes'),
        'download' => url('/app')]));

    Route::middleware('mobile.enabled')->group(function () {
        Route::post('auth/login', [\App\Http\Controllers\Api\Mobile\AuthController::class, 'login'])->middleware('throttle:10,1');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('auth/logout', [\App\Http\Controllers\Api\Mobile\AuthController::class, 'logout']);
            Route::get('me', [\App\Http\Controllers\Api\Mobile\MeController::class, 'show']);
            Route::post('devices', [\App\Http\Controllers\Api\Mobile\DeviceController::class, 'register']);
            Route::post('devices/sighting', [\App\Http\Controllers\Api\Mobile\DeviceController::class, 'sightingEndpoint']);
            Route::post('quotes', [\App\Http\Controllers\Api\Mobile\BorrowController::class, 'quote']);
            Route::post('loans', [\App\Http\Controllers\Api\Mobile\BorrowController::class, 'apply'])->middleware('throttle:5,1');
            Route::get('loans', [\App\Http\Controllers\Api\Mobile\LoansController::class, 'index']);
            Route::get('loans/{loan}', [\App\Http\Controllers\Api\Mobile\LoansController::class, 'show']);
            Route::post('loans/{loan}/repay/stk', [\App\Http\Controllers\Api\Mobile\RepayController::class, 'stk'])->middleware('throttle:5,1');
            Route::get('loans/{loan}/repay/stk/{requestId}', [\App\Http\Controllers\Api\Mobile\RepayController::class, 'stkStatus'])->whereNumber('requestId');
            Route::post('loans/{loan}/repay/sharet', [\App\Http\Controllers\Api\Mobile\RepayController::class, 'sharet'])->middleware('throttle:5,1');
            Route::post('loans/{loan}/repay/claim', [\App\Http\Controllers\Api\Mobile\RepayController::class, 'claim']);
            Route::get('statement', [\App\Http\Controllers\Api\Mobile\LoansController::class, 'statement']);
        });
    });
});
