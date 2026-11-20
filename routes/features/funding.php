<?php

use App\Http\Controllers\Funding\FundingCrmController;
use App\Http\Controllers\Funding\FundingTrackingController;
use Illuminate\Support\Facades\Route;

/* Funding CRM (owner, 20 Nov 2026): business prospects that need float, working capital, asset or trade finance.
   Cold email from sales@sharet.africa with AIM- references; opens, clicks and opt-outs come back here. */

// public: tracking image, tracked link, opt-out (one-click POST from mail apps is CSRF exempt in bootstrap/app.php)
Route::middleware('throttle:120,1,funding-track')->controller(FundingTrackingController::class)->group(function () {
    Route::get('/f/o/{track}', 'open')->name('funding.open');
    Route::get('/f/c/{track}', 'click')->name('funding.click');
    Route::get('/f/u/{track}', 'optOutForm')->name('funding.optout');
    Route::post('/f/u/{track}', 'optOut')->name('funding.optout.store');
});

Route::middleware(['auth', 'verified', 'staff', 'permission:manage funding prospects'])->prefix('staff/funding')->name('staff.funding.')
    ->controller(FundingCrmController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::post('/import', 'import')->name('import');
        Route::get('/follow-ups', 'followUps')->name('follow-ups');
        Route::get('/playbook', 'playbook')->name('playbook');
        Route::get('/campaigns', 'campaigns')->name('campaigns');
        Route::post('/campaigns', 'storeCampaign')->name('campaigns.store');
        Route::post('/campaigns/{campaign}/send', 'sendCampaign')->whereNumber('campaign')->middleware('throttle:6,1,funding-campaign')->name('campaigns.send');
        Route::get('/suppressions', 'suppressions')->name('suppressions');
        Route::post('/suppressions', 'suppress')->name('suppressions.store');
        Route::post('/activities/{activity}/done', 'done')->whereNumber('activity')->name('activities.done');
        Route::get('/{prospect}', 'show')->whereNumber('prospect')->name('show');
        Route::post('/{prospect}', 'update')->whereNumber('prospect')->name('update');
        Route::post('/{prospect}/activity', 'activity')->whereNumber('prospect')->name('activity');
        Route::post('/{prospect}/email', 'email')->whereNumber('prospect')->middleware('throttle:20,1,funding-email')->name('email');
    });
