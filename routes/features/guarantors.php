<?php

use App\Http\Controllers\Guarantors\GuarantorSignController;
use App\Http\Controllers\Guarantors\LoanGuaranteeController;
use Illuminate\Support\Facades\Route;

/* Guarantor sign-off before a loan is paid out (owner, 21 Nov 2026). App\Services\Loans\Guarantees. */

// the guarantor's own link: no login, the token is the key (only its hash is stored), rate-limited
Route::middleware('throttle:20,10,guarantor-sign')->controller(GuarantorSignController::class)->group(function () {
    Route::get('/guarantee/{token}', 'show')->name('guarantor.sign');
    Route::post('/guarantee/{token}', 'store')->name('guarantor.sign.store');
});

// the borrower: text the link to my guarantor again
Route::middleware(['auth', 'role:borrower', 'throttle:5,60,guarantor-remind'])
    ->post('/account/loans/{loan}/guarantor/remind', [LoanGuaranteeController::class, 'remind'])->name('account.loan.guarantor.remind');

// staff, from the loan page
Route::middleware(['auth', 'verified', 'staff'])->prefix('loans/{loan}/guarantee')->name('loans.guarantee.')
    ->controller(LoanGuaranteeController::class)->group(function () {
        Route::post('/send', 'send')->middleware(['permission:view loans', 'throttle:10,10,guarantor-send'])->name('send');
        Route::post('/sign-here', 'signHere')->middleware('permission:view loans')->name('sign-here');
        Route::post('/waive', 'waive')->middleware('permission:approve loans')->name('waive');
        Route::get('/', 'document')->middleware('permission:view loans')->name('document');
        Route::get('/file/{kind}', 'file')->middleware('permission:view loans')->name('file');
    });

/* Next of kin confirms they know the client (owner, 21 Nov 2026). App\Services\Kyc\KinConfirmations. */
Route::middleware('throttle:20,10,kin-confirm')->controller(\App\Http\Controllers\Guarantors\KinConfirmController::class)->group(function () {
    Route::get('/next-of-kin/{token}', 'show')->name('kin.confirm');
    Route::post('/next-of-kin/{token}', 'store')->name('kin.confirm.store');
});
Route::middleware(['auth', 'verified', 'staff'])->prefix('kyc-reviews/{user}/kin')->name('kyc-reviews.kin.')
    ->controller(\App\Http\Controllers\Guarantors\KinConfirmController::class)->group(function () {
        Route::post('/send', 'send')->middleware(['permission:view users', 'throttle:10,10,kin-send'])->name('send');
        Route::get('/file/{kind}', 'file')->middleware('permission:view users')->name('file');
    });
