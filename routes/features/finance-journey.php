<?php

use App\Http\Controllers\FinanceJourney\FinanceApplicationsController;
use App\Http\Controllers\FinanceJourney\FinanceJourneyController;
use Illuminate\Support\Facades\Route;

/* Business finance journey (owner, 20 Nov 2026; switch FINANCE_JOURNEY_ENABLED): asset and trade finance applications
   saved step by step and resumed from a private link (the link is the key; only its hash is stored). */

Route::prefix('finance')->name('finance.')->controller(FinanceJourneyController::class)->group(function () {
    Route::get('/apply', 'choose')->name('choose');
    Route::get('/apply/{product}', 'start')->whereIn('product', ['asset', 'trade'])->name('start');
    Route::post('/apply/{product}', 'store')->whereIn('product', ['asset', 'trade'])->middleware('throttle:6,10,finance-start')->name('store');
    Route::get('/resume', 'resumeForm')->name('resume');
    Route::post('/resume', 'resume')->middleware('throttle:5,10,finance-resume')->name('resume.send');
    Route::middleware('throttle:60,1,finance-link')->group(function () {
        Route::get('/a/{token}', 'show')->name('show');
        Route::get('/a/{token}/step/{step}', 'step')->name('step');
        Route::post('/a/{token}/step/{step}', 'save')->name('save');
        Route::post('/a/{token}/documents/{document}/remove', 'removeDocument')->whereNumber('document')->name('documents.remove');
        Route::post('/a/{token}/pay', 'pay')->middleware('throttle:5,5,finance-pay')->name('pay');
    });
});

Route::middleware(['auth', 'verified', 'staff', 'permission:review finance applications'])->prefix('staff/finance-applications')->name('staff.finance.')
    ->controller(FinanceApplicationsController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{application}', 'show')->whereNumber('application')->name('show');
        Route::post('/{application}/decide', 'decide')->whereNumber('application')->name('decide');
        Route::post('/{application}/loan', 'linkLoan')->whereNumber('application')->name('loan');
        Route::get('/{application}/documents/{document}', 'document')->whereNumber(['application', 'document'])->name('document');
    });
