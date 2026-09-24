<?php

use App\Http\Controllers\PayoutController;
use App\Http\Controllers\PlatformSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/admin/settings', [PlatformSettingsController::class, 'edit'])->name('platform.settings');
    Route::patch('/admin/settings', [PlatformSettingsController::class, 'update'])->middleware('throttle:20,1,platform-settings')->name('platform.settings.update');
    Route::get('/payouts', [PayoutController::class, 'index'])->name('payouts');
    Route::post('/payouts', [PayoutController::class, 'store'])->middleware('throttle:20,1,payouts-create')->name('payouts.store');
    Route::post('/payouts/{payout}/process', [PayoutController::class, 'process'])->middleware(['password.confirm', 'throttle:5,1,payouts-process'])->name('payouts.process');
    Route::post('/payouts/{payout}/reconcile', [PayoutController::class, 'reconcile'])->middleware(['password.confirm', 'throttle:5,1,payouts-reconcile'])->name('payouts.reconcile');
    Route::get('/earnings', [PayoutController::class, 'earnings'])->name('earnings');
});
