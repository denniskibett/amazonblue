<?php

use App\Http\Controllers\Partners\SubscriptionController;
use Illuminate\Support\Facades\Route;

/* Partner KYC and online subscription (owner, 23 Oct 2026; switch LENDING_PARTNER_SUBSCRIPTIONS). */
Route::middleware(['auth', 'role:partner'])->prefix('account/partner')->name('partner.')->controller(SubscriptionController::class)->group(function () {
    Route::get('/verify', 'verify')->name('verify');
    Route::post('/verify', 'saveVerify')->middleware('throttle:10,10')->name('verify.save');
    Route::get('/subscribe', 'create')->name('subscribe');
    Route::post('/subscribe', 'store')->middleware('throttle:5,10')->name('subscribe.store');
    Route::get('/subscriptions/{id}', 'show')->whereNumber('id')->name('subscriptions.show');
    Route::post('/subscriptions/{id}/sent', 'sent')->whereNumber('id')->middleware('throttle:5,10')->name('subscriptions.sent');
    Route::post('/subscriptions/{id}/cancel', 'cancel')->whereNumber('id')->name('subscriptions.cancel');
    Route::get('/subscriptions/{id}/subscription.pdf', 'file')->whereNumber('id')->name('subscriptions.file');
});

Route::middleware(['auth', 'verified', 'staff', 'permission:view partners'])
    ->get('/staff/partner-subscriptions/{id}/subscription.pdf', [SubscriptionController::class, 'staffFile'])
    ->whereNumber('id')->name('partner.subscriptions.staff-file');
