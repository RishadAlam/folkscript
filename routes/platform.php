<?php

use App\Http\Controllers\PlatformSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/admin/settings', [PlatformSettingsController::class, 'edit'])->name('platform.settings');
    Route::patch('/admin/settings', [PlatformSettingsController::class, 'update'])->middleware('throttle:20,1,platform-settings')->name('platform.settings.update');
});
