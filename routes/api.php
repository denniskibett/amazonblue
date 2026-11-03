<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// API for other systems (owner, 3 Nov 2026): keys are made on /staff/api-keys and sent as "Authorization: Bearer <key>".
Route::prefix('v1')->middleware(['api.key', 'throttle:60,1'])->group(function () {
    // checks a key works
    Route::get('ping', fn (Request $request) => response()->json(['ok' => true, 'key' => $request->attributes->get('api_key')->name, 'time' => now()->toIso8601String()]));
});
