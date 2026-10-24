<?php

use App\Http\Controllers\Aml\AmlController;
use Illuminate\Support\Facades\Route;

/* AML screening (owner, 23 Oct 2026): sanctions lists and the screening panel on business and partner reviews. */
Route::middleware(['auth', 'verified', 'staff'])->prefix('staff/aml')->name('aml.')->controller(AmlController::class)->group(function () {
    Route::get('/lists', 'lists')->middleware('permission:view kyc documents')->name('lists');
    Route::post('/lists', 'upload')->middleware('permission:access system settings')->name('lists.upload');
    Route::delete('/lists', 'remove')->middleware('permission:access system settings')->name('lists.remove');
    Route::post('/{type}/{id}/screen', 'screen')->whereIn('type', ['business', 'user'])->whereNumber('id')
        ->middleware(['permission:edit users', 'throttle:30,1'])->name('screen');
    Route::post('/{type}/{id}/decide', 'decide')->whereIn('type', ['business', 'user'])->whereNumber('id')
        ->middleware('permission:edit users')->name('decide');
});
