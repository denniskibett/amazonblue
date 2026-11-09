<?php

use App\Http\Controllers\Sharet\SharetController;
use App\Http\Controllers\Sharet\WebhookController;
use Illuminate\Support\Facades\Route;

/* Sharet Pay, the second payment rail beside the own M-Pesa paybill (9 Nov 2026; docs/SHARET_PAYMENTS.md). */

// signed by Sharet ("Sharet-Signature"); CSRF exempt in bootstrap/app.php
Route::post('/webhooks/sharet', WebhookController::class)->middleware('throttle:240,1')->name('sharet.webhook');

// borrower: pay what is due through a Sharet pay link (own loans only, 404 otherwise)
Route::middleware(['auth', 'role:borrower'])->post('/account/loans/{loan}/repay/sharet', [SharetController::class, 'borrower'])
    ->middleware('throttle:5,5')->name('account.repay.sharet');

Route::middleware(['auth', 'verified', 'staff'])->prefix('staff')->group(function () {
    Route::get('/sharet', [SharetController::class, 'index'])->middleware('permission:create repayments|disburse loans|edit partners')->name('sharet.index');
    Route::post('/loans/{loan}/sharet-pay-link', [SharetController::class, 'sendPayLink'])->middleware(['permission:create repayments', 'throttle:10,1'])->name('sharet.pay-link');
    Route::post('/loans/{loan}/sharet-payout', [SharetController::class, 'disburse'])->middleware(['permission:disburse loans', 'throttle:10,1'])->name('sharet.disburse');
    Route::post('/sharet/payouts/{payout}/cancel', [SharetController::class, 'cancel'])->middleware('permission:disburse loans|edit partners')->name('sharet.payouts.cancel');
    Route::post('/sharet/payouts/{payout}/retry', [SharetController::class, 'retry'])->middleware('permission:disburse loans|edit partners')->name('sharet.payouts.retry');
    Route::post('/integrations/sharet/test', [SharetController::class, 'test'])->middleware('permission:manage integrations|access system settings')->name('sharet.test');
});
