<?php

use App\Http\Controllers\Business\BusinessLoanController;
use App\Http\Controllers\Business\DirectorVerificationController;
use Illuminate\Support\Facades\Route;

/* Business loans (owner, 23 Oct 2026; switch LENDING_BUSINESS_LOANS): applying, director links, change requests. */

// a director's own link: no login, the token is the key (only its hash is stored), rate-limited
Route::middleware('throttle:20,10')->controller(DirectorVerificationController::class)->group(function () {
    Route::get('/verify/director/{token}', 'show')->name('directors.verify');
    Route::post('/verify/director/{token}', 'store')->name('directors.verify.store');
});

// the owner, from their business page
Route::middleware(['auth', 'role:borrower|partner'])->prefix('account/business/{business}')->name('account.business.')
    ->controller(BusinessLoanController::class)->group(function () {
        Route::get('/borrow', 'create')->name('borrow');
        Route::post('/borrow', 'store')->middleware('throttle:6,10')->name('borrow.store');
        Route::post('/members/{member}/link', 'sendLink')->middleware('throttle:10,10')->name('members.link');
        Route::post('/change', 'requestChange')->middleware('throttle:5,10')->name('change');
    });

// staff: send a director's link from the review
Route::middleware(['auth', 'verified', 'staff', 'permission:edit users'])
    ->post('/business-reviews/{business}/members/{member}/link', [\App\Http\Controllers\BusinessReviewController::class, 'sendDirectorLink'])
    ->name('business-reviews.director-link');
