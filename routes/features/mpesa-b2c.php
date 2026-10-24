<?php

use App\Http\Controllers\Mpesa\B2cController;
use Illuminate\Support\Facades\Route;

/* M-Pesa B2C payouts (owner, 23 Oct 2026; switch MPESA_B2C_ENABLED + second sign-offs). */
Route::prefix('mpesa/{secret}')->middleware('throttle:120,1')->name('mpesa.')->group(function () {
    Route::post('b2c/result', [B2cController::class, 'result'])->name('b2c.result');
    Route::post('b2c/timeout', [B2cController::class, 'timeout'])->name('b2c.timeout');
});

Route::middleware(['auth', 'verified', 'staff'])->group(function () {
    Route::get('/staff/mpesa-payouts', [B2cController::class, 'index'])->middleware('permission:view disbursements')->name('mpesa.payouts');
    Route::post('/staff/loans/{loan}/mpesa-payout', [B2cController::class, 'store'])->middleware(['permission:disburse loans', 'throttle:10,1'])->name('mpesa.payouts.store');
});
